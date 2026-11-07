<?php

namespace App\Accounting;

use App\Models\Campaign;
use App\Models\Cashbook;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\FixedAsset;
use App\Models\Income;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Payroll;
use App\Models\Production;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\ShipmentCost;
use App\Models\ShipmentReturn;
use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\Supplier;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountingPoster
{
    /**
     * Finance rows created by an operational flow. Their journal_entry_id is
     * already linked to the operational entry, so the ledger projector must
     * skip them or it would post the same event twice.
     */
    public const OPERATIONAL_FINANCE_TABLES = [
        'sales',
        'sale_returns',
        'stock_adjustments',
        'purchases',
        'shipments',
        'shipment_costs',
        'shipment_returns',
        'payrolls',
        'campaigns',
        'productions',
    ];

    public function __construct(private readonly LedgerService $ledger) {}

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

                Income::where('table_name', 'sales')
                    ->whereIn(
                        'fk_transaction_id',
                        Transaction::where('fk_reference_id', $sale->id)
                            ->where('type', Transaction::TYPE_SALE_INCOME)
                            ->pluck('id')
                    )
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

            $vat = round(min(max(0, (float) $income->vat_amount), $amount), 2);
            $net = round($amount - $vat, 2);

            $lines = [
                ['account' => 'cash', 'debit' => $amount, 'description' => $income->description],
            ];

            if ($net > 0) {
                $lines[] = ['account' => 'general_income', 'credit' => $net, 'description' => $income->description];
            }

            if ($vat > 0) {
                $lines[] = ['account' => 'sales_tax_payable', 'credit' => $vat, 'description' => "Output VAT: {$income->description}"];
            }

            $entry = $this->ledger->post(
                'income',
                'income',
                $income->id,
                "income:{$income->id}:income",
                $income->transaction?->date ?? now(),
                $income->description,
                $lines,
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

            $vat = round(min(max(0, (float) $expense->vat_amount), $amount), 2);
            $net = round($amount - $vat, 2);

            $lines = [];

            if ($net > 0) {
                $lines[] = ['account' => 'general_expense', 'debit' => $net, 'description' => $expense->description];
            }

            if ($vat > 0) {
                $lines[] = ['account' => 'input_vat', 'debit' => $vat, 'description' => "Input VAT: {$expense->description}"];
            }

            $lines[] = ['account' => 'cash', 'credit' => $amount, 'description' => $expense->description];

            $entry = $this->ledger->post(
                'expense',
                'expense',
                $expense->id,
                "expense:{$expense->id}:expense",
                $expense->transaction?->date ?? now(),
                $expense->description,
                $lines,
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

    /**
     * Post a purchase order as an inventory purchase, recovering input VAT and
     * splitting the balance between cash paid and supplier credit.
     */
    public function purchaseOrder(PurchaseOrder $order, ?int $adminId = null, ?string $suffix = null): void
    {
        $this->safe(function () use ($order, $adminId, $suffix) {
            $gross = round(max(0, (float) $order->total_amount), 2);

            if ($gross <= 0) {
                return;
            }

            $vat = round(min(max(0, (float) $order->vat_amount), $gross), 2);
            $net = round($gross - $vat, 2);
            $due = round(min(max(0, (float) $order->due_amount), $gross), 2);
            $paid = round($gross - $due, 2);

            $lines = [];

            if ($net > 0) {
                $lines[] = ['account' => 'inventory', 'debit' => $net, 'description' => "Purchase {$order->order_number}"];
            }

            if ($vat > 0) {
                $lines[] = ['account' => 'input_vat', 'debit' => $vat, 'description' => "Input VAT on {$order->order_number}"];
            }

            if ($paid > 0) {
                $lines[] = ['account' => 'cash', 'credit' => $paid, 'description' => "Paid for {$order->order_number}"];
            }

            if ($due > 0) {
                $lines[] = [
                    'account' => 'accounts_payable',
                    'credit' => $due,
                    'party_type' => 'supplier',
                    'party_id' => $order->fk_supplier_id,
                    'description' => "Due for {$order->order_number}",
                ];
            }

            $entry = $this->ledger->post(
                'purchase',
                'purchase_order',
                $order->id,
                "purchase_order:{$order->id}:purchase".($suffix ?? ''),
                $order->created_at ?? now(),
                "Purchase order #{$order->order_number}",
                $lines,
                $adminId
            );

            if ($entry) {
                DB::transaction(function () use ($order, $entry, $gross, $paid, $due, $adminId) {
                    PurchaseOrder::where('id', $order->id)
                        ->whereNull('journal_entry_id')
                        ->update(['journal_entry_id' => $entry->id]);

                    $description = "Purchase order #{$order->order_number}";
                    $date = $order->created_at?->toDateString() ?? now()->toDateString();

                    $transaction = Transaction::withTrashed()
                        ->where('journal_entry_id', $entry->id)
                        ->where('type', Transaction::TYPE_PURCHASE)
                        ->first();

                    if (! $transaction) {
                        $transaction = Transaction::create([
                            'date' => $date,
                            'type' => Transaction::TYPE_PURCHASE,
                            'fk_reference_id' => $order->id,
                            'amount' => $gross,
                            'paid_amount' => $paid,
                            'due_amount' => $due,
                            'journal_entry_id' => $entry->id,
                            'created_by' => $adminId,
                        ]);
                    }

                    $cashbook = Cashbook::withTrashed()
                        ->where('journal_entry_id', $entry->id)
                        ->where('table_name', 'purchases')
                        ->first();

                    if (! $cashbook) {
                        Cashbook::create([
                            'table_name' => 'purchases',
                            'fk_reference_id' => $order->id,
                            'description' => $description,
                            'in_amount' => 0,
                            'out_amount' => $paid,
                            'amount_payable' => $due,
                            'amount_receivable' => 0,
                            'journal_entry_id' => $entry->id,
                            'created_by' => $adminId,
                        ]);
                    }
                });
            }
        });
    }

    public function saleReturn(SaleReturn $return, Sale $sale, ?int $adminId = null): void
    {
        $this->safe(function () use ($return, $sale, $adminId) {
            $refund = round((float) $return->refund_amount, 2);
            $date = $return->created_at ?? now();

            if ($refund > 0) {
                $vat = round(min(max(0, (float) $return->vat_amount), $refund), 2);
                $net = round($refund - $vat, 2);

                $lines = [];

                if ($net > 0) {
                    $lines[] = ['account' => 'sales_returns', 'debit' => $net];
                }

                if ($vat > 0) {
                    $lines[] = ['account' => 'sales_tax_payable', 'debit' => $vat, 'description' => 'Output VAT reversed'];
                }

                $lines[] = ['account' => 'cash', 'credit' => $refund];

                $entry = $this->ledger->post(
                    'saleReturn',
                    'sale_return',
                    $return->id,
                    "sale_return:{$return->id}:refund",
                    $date,
                    "Refund for return #{$return->id} (Sale {$sale->invoice_id})",
                    $lines,
                    $adminId
                );

                if ($entry) {
                    DB::transaction(function () use ($return, $sale, $entry, $refund, $vat, $net, $adminId) {
                        SaleReturn::where('id', $return->id)
                            ->whereNull('journal_entry_id')
                            ->update(['journal_entry_id' => $entry->id]);

                        $description = "Refund for return #{$return->id} (Sale {$sale->invoice_id})";

                        $transaction = Transaction::where('type', Transaction::TYPE_MISC_EXPENSE)
                            ->where('fk_reference_id', $return->id)
                            ->first();

                        if (! $transaction) {
                            $transaction = Transaction::create([
                                'date' => $return->created_at?->toDateString() ?? now()->toDateString(),
                                'type' => Transaction::TYPE_MISC_EXPENSE,
                                'fk_reference_id' => $return->id,
                                'amount' => $refund,
                                'paid_amount' => $refund,
                                'due_amount' => 0,
                                'journal_entry_id' => $entry->id,
                                'created_by' => $adminId,
                            ]);
                        } elseif (! $transaction->journal_entry_id) {
                            $transaction->update(['journal_entry_id' => $entry->id]);
                        }

                        $cashbook = Cashbook::where('table_name', 'sale_returns')
                            ->where('fk_reference_id', $return->id)
                            ->first();

                        if (! $cashbook) {
                            Cashbook::create([
                                'table_name' => 'sale_returns',
                                'fk_reference_id' => $return->id,
                                'description' => $description,
                                'in_amount' => 0,
                                'out_amount' => $refund,
                                'amount_payable' => 0,
                                'amount_receivable' => 0,
                                'journal_entry_id' => $entry->id,
                                'created_by' => $adminId,
                            ]);
                        } elseif (! $cashbook->journal_entry_id) {
                            $cashbook->update(['journal_entry_id' => $entry->id]);
                        }

                        $this->recordExpenseMirror(
                            'sale_returns',
                            $transaction->id,
                            $description,
                            $refund,
                            $entry->id,
                            $adminId,
                            $return->vat_code,
                            $return->vat_rate !== null ? (float) $return->vat_rate : null,
                            $net,
                            $vat
                        );
                    });
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

    public function supplierPayment(Supplier $supplier, float $amount, int $transactionId, ?string $description = null, ?int $adminId = null, ?int $cashbookId = null): void
    {
        $this->safe(function () use ($supplier, $amount, $transactionId, $description, $adminId, $cashbookId) {
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

                if ($cashbookId) {
                    Cashbook::where('id', $cashbookId)
                        ->whereNull('journal_entry_id')
                        ->update(['journal_entry_id' => $entry->id]);
                } else {
                    Cashbook::where('table_name', 'suppliers')
                        ->where('fk_reference_id', $supplier->id)
                        ->whereNull('journal_entry_id')
                        ->where('out_amount', $amount)
                        ->latest('id')
                        ->first()
                        ?->update(['journal_entry_id' => $entry->id]);
                }
            }
        });
    }

    /**
     * Capitalise an inbound shipment cost (freight, duty, handling) into
     * inventory. Paid costs reduce cash; unpaid costs increase payables.
     */
    public function shipmentCost(ShipmentCost $cost, ?int $adminId = null): void
    {
        $this->safe(function () use ($cost, $adminId) {
            $amount = round(max(0, (float) $cost->amount), 2);

            if ($amount <= 0) {
                return;
            }

            $cost->loadMissing(['shipment', 'supplier']);

            $tracking = $cost->shipment?->tracking_number ?? ('#'.$cost->fk_shipment_id);
            $description = $cost->description
                ?: ucfirst($cost->cost_type)." for shipment {$tracking}";

            $lines = [
                ['account' => 'inventory', 'debit' => $amount, 'description' => $description],
            ];

            // A cost paid later started life as a payable, even though its
            // current status is now "paid". Replays must keep that split.
            $paidAtCreation = $cost->isPaid() && ! $cost->fk_payment_transaction_id;

            if ($paidAtCreation) {
                $lines[] = ['account' => 'cash', 'credit' => $amount, 'description' => $description];
            } else {
                $payable = ['account' => 'accounts_payable', 'credit' => $amount, 'description' => $description];

                if ($cost->fk_supplier_id) {
                    $payable['party_type'] = 'supplier';
                    $payable['party_id'] = $cost->fk_supplier_id;
                }

                $lines[] = $payable;
            }

            $entry = $this->ledger->post(
                'shipmentCost',
                'shipment_cost',
                $cost->id,
                "shipment_cost:{$cost->id}:cost",
                $cost->cost_date ?? now(),
                $description,
                $lines,
                $adminId
            );

            if ($entry) {
                ShipmentCost::where('id', $cost->id)->update(['journal_entry_id' => $entry->id]);

                Transaction::where('id', $cost->fk_transaction_id)
                    ->whereNull('journal_entry_id')
                    ->update(['journal_entry_id' => $entry->id]);

                if ($cost->fk_cashbook_id) {
                    Cashbook::where('id', $cost->fk_cashbook_id)
                        ->whereNull('journal_entry_id')
                        ->update(['journal_entry_id' => $entry->id]);
                }
            }
        });
    }

    /**
     * Post a manual stock adjustment to the ledger.
     *
     * Damage and downward corrections write inventory off as a loss. A return
     * adds stock back and reverses that loss. Value is taken at cost
     * (stocks.buy_price), never sale price, because no sale happened.
     */
    public function stockAdjustment(StockAdjustment $adjustment, ?int $adminId = null): void
    {
        $this->safe(function () use ($adjustment, $adminId) {
            $adjustment->loadMissing('stock.product');

            $stock = $adjustment->stock;

            if (! $stock) {
                return;
            }

            $cost = round((int) $adjustment->quantity * (float) ($stock->buy_price ?? 0), 2);

            if ($cost <= 0) {
                return;
            }

            $label = $stock->product?->name ?? ('stock #'.$stock->id);
            $description = ucfirst($adjustment->reason)." stock adjustment for {$label}";

            // A return puts stock back on the shelf, so it reverses the loss.
            // Damage and corrections take stock out, so they post a loss.
            $lines = $adjustment->reason === 'return'
                ? [
                    ['account' => 'inventory', 'debit' => $cost, 'description' => $description],
                    ['account' => 'inventory_adjustment', 'credit' => $cost, 'description' => $description],
                ]
                : [
                    ['account' => 'inventory_adjustment', 'debit' => $cost, 'description' => $description],
                    ['account' => 'inventory', 'credit' => $cost, 'description' => $description],
                ];

            $entry = $this->ledger->post(
                'stockAdjustment',
                'stock_adjustment',
                $adjustment->id,
                "stock_adjustment:{$adjustment->id}",
                $adjustment->created_at ?? now(),
                "Stock adjustment #{$adjustment->id}",
                $lines,
                $adminId
            );

            if (! $entry) {
                return;
            }

            StockAdjustment::where('id', $adjustment->id)
                ->whereNull('journal_entry_id')
                ->update(['journal_entry_id' => $entry->id]);

            $this->recordStockAdjustmentFinance($adjustment, $label, $cost, $entry, $adminId);
        });
    }

    /**
     * Mirror a stock adjustment into the Finance module without moving cash.
     *
     * Damage/corrections create a non-cash Expense row; returns create a
     * non-cash Income row. Both link to the same journal entry posted by
     * stockAdjustment(), so the ledger is not posted twice.
     */
    private function recordStockAdjustmentFinance(
        StockAdjustment $adjustment,
        string $label,
        float $cost,
        JournalEntry $entry,
        ?int $adminId
    ): void {
        $isReturn = $adjustment->reason === 'return';
        $financeModel = $isReturn ? Income::class : Expense::class;

        if ($financeModel::withTrashed()->where('journal_entry_id', $entry->id)->exists()) {
            return;
        }

        $type = $isReturn
            ? Transaction::TYPE_STOCK_ADJUSTMENT_GAIN
            : Transaction::TYPE_STOCK_ADJUSTMENT_LOSS;

        $transaction = Transaction::where('type', $type)
            ->where('fk_reference_id', $adjustment->id)
            ->first();

        if (! $transaction) {
            $transaction = Transaction::create([
                'date' => $adjustment->created_at ?? now(),
                'type' => $type,
                'fk_reference_id' => $adjustment->id,
                'amount' => $cost,
                'paid_amount' => 0,
                'due_amount' => 0,
                'journal_entry_id' => $entry->id,
                'created_by' => $adminId,
            ]);
        } elseif (! $transaction->journal_entry_id) {
            $transaction->update(['journal_entry_id' => $entry->id]);
        }

        $description = ucfirst($adjustment->reason)." stock adjustment for {$label} (non-cash)";

        $financeModel::create([
            'table_name' => 'stock_adjustments',
            'fk_transaction_id' => $transaction->id,
            'description' => $description,
            'amount' => $cost,
            'vat_code' => null,
            'vat_rate' => 0,
            'net_amount' => $cost,
            'vat_amount' => 0,
            'journal_entry_id' => $entry->id,
            'created_by' => $adminId,
        ]);
    }

    /**
     * Record the purchase of a fixed asset as a cash outflow.
     */
    public function fixedAsset(FixedAsset $asset, ?int $adminId = null): void
    {
        $this->safe(function () use ($asset, $adminId) {
            $amount = round(max(0, (float) $asset->price), 2);

            if ($amount <= 0) {
                return;
            }

            $sourceKey = $this->operationalKey('fixed_asset', $asset->id, $asset->accounting_version);

            if (JournalEntry::withTrashed()->where('source_key', $sourceKey)->exists()) {
                return;
            }

            DB::transaction(function () use ($asset, $amount, $sourceKey, $adminId) {
                $description = "Fixed asset: {$asset->name}";

                $transaction = Transaction::create([
                    'date' => now()->toDateString(),
                    'type' => Transaction::TYPE_FIXED_ASSET,
                    'fk_reference_id' => $asset->id,
                    'amount' => $amount,
                    'paid_amount' => $amount,
                    'due_amount' => 0,
                    'created_by' => $adminId,
                ]);

                $cashbook = Cashbook::create([
                    'table_name' => 'fixed_assets',
                    'fk_reference_id' => $asset->id,
                    'description' => $description,
                    'in_amount' => 0,
                    'out_amount' => $amount,
                    'amount_payable' => 0,
                    'amount_receivable' => 0,
                    'created_by' => $adminId,
                ]);

                $entry = $this->ledger->post(
                    'fixedAsset',
                    'fixed_asset',
                    $asset->id,
                    $sourceKey,
                    now(),
                    $description,
                    [
                        ['account' => 'fixed_assets', 'debit' => $amount, 'description' => $description],
                        ['account' => 'cash', 'credit' => $amount, 'description' => $description],
                    ],
                    $adminId
                );

                if ($entry) {
                    $transaction->update(['journal_entry_id' => $entry->id]);
                    $cashbook->update(['journal_entry_id' => $entry->id]);
                    FixedAsset::where('id', $asset->id)->update(['journal_entry_id' => $entry->id]);
                }
            });
        });
    }

    /**
     * Record a payroll run as salary expense paid from cash.
     */
    public function payroll(Payroll $payroll, ?int $adminId = null): void
    {
        $this->safe(function () use ($payroll, $adminId) {
            $amount = round(max(0, (float) $payroll->net_salary), 2);

            if ($amount <= 0) {
                return;
            }

            $sourceKey = $this->operationalKey('payroll', $payroll->id, $payroll->accounting_version);

            if (JournalEntry::withTrashed()->where('source_key', $sourceKey)->exists()) {
                return;
            }

            DB::transaction(function () use ($payroll, $amount, $sourceKey, $adminId) {
                $payroll->loadMissing('employee');
                $name = trim(($payroll->employee->first_name ?? '').' '.($payroll->employee->last_name ?? ''));
                $description = $name !== '' ? "Salary paid to {$name}" : "Salary payment #{$payroll->id}";
                $date = $payroll->pay_date ?? now();

                $transaction = Transaction::create([
                    'date' => $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : (string) $date,
                    'type' => Transaction::TYPE_SALARY_PAYMENT,
                    'fk_reference_id' => $payroll->id,
                    'amount' => $amount,
                    'paid_amount' => $amount,
                    'due_amount' => 0,
                    'created_by' => $adminId,
                ]);

                $cashbook = Cashbook::create([
                    'table_name' => 'payrolls',
                    'fk_reference_id' => $payroll->id,
                    'description' => $description,
                    'in_amount' => 0,
                    'out_amount' => $amount,
                    'amount_payable' => 0,
                    'amount_receivable' => 0,
                    'created_by' => $adminId,
                ]);

                $entry = $this->ledger->post(
                    'salaryPayment',
                    'payroll',
                    $payroll->id,
                    $sourceKey,
                    $date,
                    $description,
                    [
                        ['account' => 'salaries_expense', 'debit' => $amount, 'description' => $description],
                        ['account' => 'cash', 'credit' => $amount, 'description' => $description],
                    ],
                    $adminId
                );

                if ($entry) {
                    $transaction->update(['journal_entry_id' => $entry->id]);
                    $cashbook->update(['journal_entry_id' => $entry->id]);
                    Payroll::where('id', $payroll->id)->update(['journal_entry_id' => $entry->id]);

                    $this->recordExpenseMirror(
                        'payrolls',
                        $transaction->id,
                        $description,
                        $amount,
                        $entry->id,
                        $adminId
                    );
                }
            });
        });
    }

    /**
     * Post the actual spend of a campaign. The budget stays a memo only.
     */
    public function campaignSpend(Campaign $campaign, ?int $adminId = null): void
    {
        $this->safe(function () use ($campaign, $adminId) {
            $amount = round(max(0, (float) $campaign->spent_amount), 2);

            if ($amount <= 0) {
                return;
            }

            $sourceKey = $this->operationalKey('campaign', $campaign->id, $campaign->accounting_version);

            if (JournalEntry::withTrashed()->where('source_key', $sourceKey)->exists()) {
                return;
            }

            DB::transaction(function () use ($campaign, $amount, $sourceKey, $adminId) {
                $description = "Campaign spend: {$campaign->name}";

                $transaction = Transaction::create([
                    'date' => now()->toDateString(),
                    'type' => Transaction::TYPE_CAMPAIGN_EXPENSE,
                    'fk_reference_id' => $campaign->id,
                    'amount' => $amount,
                    'paid_amount' => $amount,
                    'due_amount' => 0,
                    'created_by' => $adminId,
                ]);

                $cashbook = Cashbook::create([
                    'table_name' => 'campaigns',
                    'fk_reference_id' => $campaign->id,
                    'description' => $description,
                    'in_amount' => 0,
                    'out_amount' => $amount,
                    'amount_payable' => 0,
                    'amount_receivable' => 0,
                    'created_by' => $adminId,
                ]);

                $entry = $this->ledger->post(
                    'campaignExpense',
                    'campaign',
                    $campaign->id,
                    $sourceKey,
                    now(),
                    $description,
                    [
                        ['account' => 'marketing_expense', 'debit' => $amount, 'description' => $description],
                        ['account' => 'cash', 'credit' => $amount, 'description' => $description],
                    ],
                    $adminId
                );

                if ($entry) {
                    $transaction->update(['journal_entry_id' => $entry->id]);
                    $cashbook->update(['journal_entry_id' => $entry->id]);
                    Campaign::where('id', $campaign->id)->update(['journal_entry_id' => $entry->id]);

                    $this->recordExpenseMirror(
                        'campaigns',
                        $transaction->id,
                        $description,
                        $amount,
                        $entry->id,
                        $adminId
                    );
                }
            });
        });
    }

    /**
     * Capitalise production cost (production + other cost) into inventory and
     * record the matching cash outflow.
     */
    public function production(Production $production, ?int $adminId = null): void
    {
        $this->safe(function () use ($production, $adminId) {
            $amount = round(max(0, (float) $production->production_cost) + max(0, (float) $production->other_cost), 2);

            if ($amount <= 0) {
                return;
            }

            $sourceKey = $this->operationalKey('production', $production->id, $production->accounting_version);

            if (JournalEntry::withTrashed()->where('source_key', $sourceKey)->exists()) {
                return;
            }

            DB::transaction(function () use ($production, $amount, $sourceKey, $adminId) {
                $description = "Production run #{$production->id}";

                $transaction = Transaction::create([
                    'date' => now()->toDateString(),
                    'type' => Transaction::TYPE_BOM_EXPENSE,
                    'fk_reference_id' => $production->id,
                    'amount' => $amount,
                    'paid_amount' => $amount,
                    'due_amount' => 0,
                    'created_by' => $adminId,
                ]);

                $cashbook = Cashbook::create([
                    'table_name' => 'productions',
                    'fk_reference_id' => $production->id,
                    'description' => $description,
                    'in_amount' => 0,
                    'out_amount' => $amount,
                    'amount_payable' => 0,
                    'amount_receivable' => 0,
                    'created_by' => $adminId,
                ]);

                $entry = $this->ledger->post(
                    'bomExpense',
                    'production',
                    $production->id,
                    $sourceKey,
                    now(),
                    $description,
                    [
                        ['account' => 'inventory', 'debit' => $amount, 'description' => $description],
                        ['account' => 'cash', 'credit' => $amount, 'description' => $description],
                    ],
                    $adminId
                );

                if ($entry) {
                    $transaction->update(['journal_entry_id' => $entry->id]);
                    $cashbook->update(['journal_entry_id' => $entry->id]);
                    Production::where('id', $production->id)->update(['journal_entry_id' => $entry->id]);
                }
            });
        });
    }

    /**
     * Post a new stock line as opening stock. No cash moves: the value is
     * balanced against opening-balance equity.
     */
    public function stockIn(Stock $stock, ?int $adminId = null): void
    {
        $this->safe(function () use ($stock, $adminId) {
            $amount = round(max(0, (int) $stock->quantity) * max(0, (float) $stock->buy_price), 2);

            if ($amount <= 0) {
                return;
            }

            $sourceKey = $this->operationalKey('stock', $stock->id, $stock->accounting_version);

            if (JournalEntry::withTrashed()->where('source_key', $sourceKey)->exists()) {
                return;
            }

            $stock->loadMissing('product');
            $label = $stock->product?->name ?? ('stock #'.$stock->id);
            $description = "Opening stock for {$label}";

            $entry = $this->ledger->post(
                'stockIn',
                'stock',
                $stock->id,
                $sourceKey,
                now(),
                $description,
                [
                    ['account' => 'inventory', 'debit' => $amount, 'description' => $description],
                    ['account' => 'opening_balance_equity', 'credit' => $amount, 'description' => $description],
                ],
                $adminId
            );

            if ($entry) {
                Stock::where('id', $stock->id)->update(['journal_entry_id' => $entry->id]);
            }
        });
    }

    /**
     * Post a supplier opening balance. A positive balance is an advance paid
     * to the supplier; a negative balance is money owed to the supplier.
     *
     * The entry only covers the part of the balance the ledger does not already
     * know about, so it is safe to replay for legacy suppliers whose fund
     * deposits and purchase postings were recorded before opening balances
     * were wired into accounting.
     */
    public function supplierOpening(Supplier $supplier, ?int $adminId = null): void
    {
        $this->safe(function () use ($supplier, $adminId) {
            $balance = round((float) $supplier->balance, 2);

            if (abs($balance) < 0.01) {
                return;
            }

            $sourceKey = $this->operationalKey('supplier_opening', $supplier->id, $supplier->accounting_version);

            if (JournalEntry::withTrashed()->where('source_key', $sourceKey)->exists()) {
                return;
            }

            $delta = round($balance - $this->supplierLedgerPosition($supplier->id), 2);

            if (abs($delta) < 0.01) {
                return;
            }

            $description = "Opening balance for {$supplier->name}";

            $lines = $delta > 0
                ? [
                    ['account' => 'supplier_advances', 'debit' => $delta, 'party_type' => 'supplier', 'party_id' => $supplier->id, 'description' => $description],
                    ['account' => 'opening_balance_equity', 'credit' => $delta, 'description' => $description],
                ]
                : [
                    ['account' => 'opening_balance_equity', 'debit' => abs($delta), 'description' => $description],
                    ['account' => 'accounts_payable', 'credit' => abs($delta), 'party_type' => 'supplier', 'party_id' => $supplier->id, 'description' => $description],
                ];

            $entry = $this->ledger->post(
                'supplierOpening',
                'supplier',
                $supplier->id,
                $sourceKey,
                now(),
                $description,
                $lines,
                $adminId
            );

            if ($entry) {
                Supplier::where('id', $supplier->id)->update(['journal_entry_id' => $entry->id]);
            }
        });
    }

    /**
     * Signed ledger position for a supplier: advances are positive, amounts we
     * owe are negative. Mirrors the sign of the suppliers.balance column.
     */
    private function supplierLedgerPosition(int $supplierId): float
    {
        $advances = (float) JournalLine::query()
            ->whereHas('account', fn ($query) => $query->where('system_key', 'supplier_advances'))
            ->where('party_type', 'supplier')
            ->where('party_id', $supplierId)
            ->whereHas('entry', fn ($query) => $query->posted())
            ->selectRaw('COALESCE(SUM(debit - credit), 0) as balance')
            ->value('balance');

        $payable = (float) JournalLine::query()
            ->whereHas('account', fn ($query) => $query->where('system_key', 'accounts_payable'))
            ->where('party_type', 'supplier')
            ->where('party_id', $supplierId)
            ->whereHas('entry', fn ($query) => $query->posted())
            ->selectRaw('COALESCE(SUM(credit - debit), 0) as balance')
            ->value('balance');

        return round($advances - $payable, 2);
    }

    /**
     * Reverse only the opening-balance entries for a supplier. Fund deposits and
     * payments are separate cash events and must survive a supplier edit/delete.
     */
    public function reverseSupplierOpening(Supplier $supplier, ?int $adminId = null): void
    {
        $this->safe(function () use ($supplier, $adminId) {
            $entries = JournalEntry::where('source_type', 'supplier')
                ->where('source_id', $supplier->id)
                ->where('event_type', 'supplierOpening')
                ->where('source_key', 'not like', '%:reversal')
                ->get();

            foreach ($entries as $entry) {
                $reversalKey = $entry->source_key.':reversal';

                if (! JournalEntry::withTrashed()->where('source_key', $reversalKey)->exists()) {
                    $this->ledger->reverse($entry, "Reversal of {$entry->entry_no}", $adminId);
                }
            }
        });
    }

    /**
     * Reverse a purchase by returning goods to the supplier. The payable is
     * reduced first, then any supplier advance, and inventory comes out.
     */
    public function shipmentReturn(ShipmentReturn $return, ?int $adminId = null): void
    {
        $this->safe(function () use ($return, $adminId) {
            if (! in_array($return->status, ['processed', 'completed'], true)) {
                return;
            }

            $amount = round(max(0, (float) $return->invoice_amount), 2);

            if ($amount <= 0) {
                return;
            }

            $sourceKey = $this->operationalKey('shipment_return', $return->id, $return->accounting_version);

            if (JournalEntry::withTrashed()->where('source_key', $sourceKey)->exists()) {
                return;
            }

            $return->loadMissing('purchaseOrder');
            $order = $return->purchaseOrder;
            $due = round(min($amount, max(0, (float) ($order->due_amount ?? 0))), 2);
            $advance = round($amount - $due, 2);
            $supplierId = $order->fk_supplier_id ?? null;
            $description = "Goods returned to supplier (return #{$return->id})";

            $lines = [];

            if ($due > 0) {
                $lines[] = [
                    'account' => 'accounts_payable',
                    'debit' => $due,
                    'party_type' => $supplierId ? 'supplier' : null,
                    'party_id' => $supplierId,
                    'description' => $description,
                ];
            }

            if ($advance > 0) {
                $lines[] = [
                    'account' => 'supplier_advances',
                    'debit' => $advance,
                    'party_type' => $supplierId ? 'supplier' : null,
                    'party_id' => $supplierId,
                    'description' => $description,
                ];
            }

            $lines[] = ['account' => 'inventory', 'credit' => $amount, 'description' => $description];

            $entry = $this->ledger->post(
                'shipmentReturn',
                'shipment_return',
                $return->id,
                $sourceKey,
                now(),
                $description,
                $lines,
                $adminId
            );

            if ($entry) {
                DB::transaction(function () use ($return, $entry, $amount, $due, $advance, $description, $adminId) {
                    ShipmentReturn::where('id', $return->id)->update(['journal_entry_id' => $entry->id]);

                    $date = $return->created_at?->toDateString() ?? now()->toDateString();

                    $transaction = Transaction::withTrashed()
                        ->where('journal_entry_id', $entry->id)
                        ->where('type', Transaction::TYPE_PURCHASE_RETURN)
                        ->first();

                    if (! $transaction) {
                        Transaction::create([
                            'date' => $date,
                            'type' => Transaction::TYPE_PURCHASE_RETURN,
                            'fk_reference_id' => $return->id,
                            'amount' => $amount,
                            'paid_amount' => 0,
                            'due_amount' => 0,
                            'journal_entry_id' => $entry->id,
                            'created_by' => $adminId,
                        ]);
                    }

                    $cashbook = Cashbook::withTrashed()
                        ->where('journal_entry_id', $entry->id)
                        ->where('table_name', 'shipment_returns')
                        ->first();

                    if (! $cashbook) {
                        Cashbook::create([
                            'table_name' => 'shipment_returns',
                            'fk_reference_id' => $return->id,
                            'description' => $description,
                            'in_amount' => 0,
                            'out_amount' => 0,
                            'amount_payable' => -$due,
                            'amount_receivable' => $advance,
                            'journal_entry_id' => $entry->id,
                            'created_by' => $adminId,
                        ]);
                    }
                });
            }
        });
    }

    /**
     * Reverse every posted entry for a source and soft-delete the simple-layer
     * rows that were linked to them. Used before a replacement posting and on
     * delete.
     */
    public function reverseOperational(string $sourceType, int $sourceId, ?int $adminId = null): void
    {
        $this->safe(function () use ($sourceType, $sourceId, $adminId) {
            $entries = JournalEntry::where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->where('source_key', 'not like', '%:reversal')
                ->get();

            foreach ($entries as $entry) {
                $reversalKey = $entry->source_key.':reversal';

                if (! JournalEntry::withTrashed()->where('source_key', $reversalKey)->exists()) {
                    $this->ledger->reverse($entry, "Reversal of {$entry->entry_no}", $adminId);
                }

                Transaction::where('journal_entry_id', $entry->id)->delete();
                Cashbook::where('journal_entry_id', $entry->id)->delete();
                Income::where('journal_entry_id', $entry->id)->delete();
                Expense::where('journal_entry_id', $entry->id)->delete();
            }
        });
    }

    /**
     * Mirror an operational cost into the Finance module without posting the
     * ledger again. The row shares the operational journal entry, so the
     * projector excludes it (see OPERATIONAL_FINANCE_TABLES).
     */
    private function recordExpenseMirror(
        string $tableName,
        int $transactionId,
        string $description,
        float $amount,
        int $entryId,
        ?int $adminId,
        ?string $vatCode = null,
        ?float $vatRate = null,
        ?float $netAmount = null,
        ?float $vatAmount = null
    ): void {
        if (Expense::withTrashed()->where('journal_entry_id', $entryId)->exists()) {
            return;
        }

        Expense::create([
            'table_name' => $tableName,
            'fk_transaction_id' => $transactionId,
            'description' => $description,
            'amount' => round($amount, 2),
            'vat_code' => $vatCode,
            'vat_rate' => $vatRate,
            'net_amount' => round($netAmount ?? $amount, 2),
            'vat_amount' => round($vatAmount ?? 0, 2),
            'journal_entry_id' => $entryId,
            'created_by' => $adminId,
        ]);
    }

    private function operationalKey(string $base, int $id, ?int $version): string
    {
        return "{$base}:{$id}:v".max(1, (int) $version);
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
