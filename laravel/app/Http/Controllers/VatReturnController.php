<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\Transaction;
use App\Services\VatCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * UK VAT return (9 boxes) built from sales, purchases, expenses, returns and
 * reconciled against the double-entry ledger.
 */
class VatReturnController extends Controller
{
    public function __construct(private readonly VatCalculator $vat) {}

    public function index(Request $request)
    {
        $now = Carbon::now();
        $startDate = $request->input('start_date', $now->copy()->startOfQuarter()->toDateString());
        $endDate = $request->input('end_date', $now->copy()->endOfQuarter()->toDateString());
        $box = $request->input('box');

        $sales = DB::table('sales')
            ->join('transactions', function ($join) {
                $join->on('sales.id', '=', 'transactions.fk_reference_id')
                    ->where('transactions.type', Transaction::TYPE_SALE_INCOME);
            })
            ->whereNull('sales.deleted_at')
            ->whereBetween('transactions.date', [$startDate, $endDate]);

        $incomes = DB::table('incomes')
            ->join('transactions', 'incomes.fk_transaction_id', '=', 'transactions.id')
            ->whereNull('incomes.deleted_at')
            ->where(function ($query) {
                $query->whereNull('incomes.table_name')
                    ->orWhere('incomes.table_name', '!=', 'sales');
            })
            ->whereBetween('transactions.date', [$startDate, $endDate]);

        $returns = DB::table('sale_returns')
            ->join('journal_entries', 'sale_returns.journal_entry_id', '=', 'journal_entries.id')
            ->whereNull('sale_returns.deleted_at')
            ->whereNull('journal_entries.deleted_at')
            ->whereIn('sale_returns.status', ['approved', 'completed'])
            // Use the accounting date so returns land in the same period as the
            // ledger reversal. sale_returns has no timestamps, so created_at is
            // null for rows created through the app.
            ->whereBetween('journal_entries.date', [$startDate, $endDate]);

        $expenses = DB::table('expenses')
            ->join('transactions', 'expenses.fk_transaction_id', '=', 'transactions.id')
            ->whereNull('expenses.deleted_at')
            ->where(function ($query) {
                // Sale return refunds reverse output VAT; they are not inputs.
                $query->whereNull('expenses.table_name')
                    ->orWhere('expenses.table_name', '!=', 'sale_returns');
            })
            ->whereBetween('transactions.date', [$startDate, $endDate]);

        $purchases = DB::table('purchase_orders')
            ->join('journal_entries', 'purchase_orders.journal_entry_id', '=', 'journal_entries.id')
            ->whereNull('purchase_orders.deleted_at')
            ->whereNull('journal_entries.deleted_at')
            ->whereBetween('journal_entries.date', [$startDate, $endDate]);

        $outputVat = round(
            (float) (clone $sales)->sum('sales.vat_amount')
            + (float) (clone $incomes)->sum('incomes.vat_amount')
            - (float) (clone $returns)->sum('sale_returns.vat_amount'),
            2
        );

        $inputVat = round(
            (float) (clone $expenses)->sum('expenses.vat_amount')
            + (float) (clone $purchases)->sum('purchase_orders.vat_amount'),
            2
        );

        $netSales = round(
            (float) (clone $sales)->sum('sales.net_price')
            + (float) (clone $incomes)->sum('incomes.net_amount')
            - (float) (clone $returns)->sum('sale_returns.net_amount'),
            2
        );

        $netPurchases = round(
            (float) (clone $expenses)->sum('expenses.net_amount')
            + (float) (clone $purchases)->sum('purchase_orders.net_amount'),
            2
        );

        $boxes = [
            1 => ['label' => 'VAT due on sales and other outputs', 'value' => $outputVat],
            2 => ['label' => 'VAT due on acquisitions from EU member states', 'value' => 0.0],
            3 => ['label' => 'Total VAT due', 'value' => round($outputVat + 0.0, 2)],
            4 => ['label' => 'VAT reclaimed on purchases and other inputs', 'value' => $inputVat],
            5 => ['label' => 'Net VAT to pay to HMRC or reclaim', 'value' => round($outputVat - $inputVat, 2)],
            6 => ['label' => 'Total value of sales and other outputs excluding VAT', 'value' => $netSales],
            7 => ['label' => 'Total value of purchases and other inputs excluding VAT', 'value' => $netPurchases],
            8 => ['label' => 'Total value of goods supplied to EU member states', 'value' => 0.0],
            9 => ['label' => 'Total value of goods acquired from EU member states', 'value' => 0.0],
        ];

        $ledgerOutputVat = $this->ledgerBalance('sales_tax_payable', $startDate, $endDate, 'credit');
        $ledgerInputVat = $this->ledgerBalance('input_vat', $startDate, $endDate, 'debit');

        $reconciliation = [
            'output' => [
                'report' => $outputVat,
                'ledger' => $ledgerOutputVat,
                'difference' => round($outputVat - $ledgerOutputVat, 2),
            ],
            'input' => [
                'report' => $inputVat,
                'ledger' => $ledgerInputVat,
                'difference' => round($inputVat - $ledgerInputVat, 2),
            ],
        ];

        $drillDown = $this->drillDown($box, $sales, $incomes, $returns, $expenses, $purchases);
        $rateBreakdown = $this->rateBreakdown($startDate, $endDate);

        $currencySymbol = Configuration::get('currency_symbol', '$');

        if ($request->expectsJson()) {
            return response()->json([
                'period' => ['start_date' => $startDate, 'end_date' => $endDate],
                'boxes' => $boxes,
                'reconciliation' => $reconciliation,
                'rate_breakdown' => $rateBreakdown,
                'drill_down' => $drillDown,
            ]);
        }

        return view('reports.vat-return', compact(
            'currencySymbol',
            'startDate', 'endDate', 'box', 'boxes',
            'reconciliation', 'rateBreakdown', 'drillDown'
        ));
    }

    private function ledgerBalance(string $systemKey, string $startDate, string $endDate, string $normal): float
    {
        $query = DB::table('journal_lines')
            ->join('journal_entries', 'journal_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('accounts', 'journal_lines.account_id', '=', 'accounts.id')
            ->where('accounts.system_key', $systemKey)
            ->whereNull('journal_entries.deleted_at')
            ->whereBetween('journal_entries.date', [$startDate, $endDate]);

        if ($normal === 'debit') {
            return round((float) $query->selectRaw('COALESCE(SUM(journal_lines.debit - journal_lines.credit), 0) as total')->value('total'), 2);
        }

        return round((float) $query->selectRaw('COALESCE(SUM(journal_lines.credit - journal_lines.debit), 0) as total')->value('total'), 2);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function drillDown(
        ?string $box,
        $sales,
        $incomes,
        $returns,
        $expenses,
        $purchases
    ): array {
        if (! in_array($box, ['1', '4', '6', '7'], true)) {
            return [];
        }

        $rows = [];

        if (in_array($box, ['1', '6'], true)) {
            foreach ((clone $sales)->orderBy('transactions.date')->get([
                'transactions.date', 'sales.invoice_id', 'sales.type',
                'sales.net_price', 'sales.vat_amount', 'sales.grand_total',
            ]) as $sale) {
                $rows[] = [
                    'date' => $sale->date,
                    'reference' => $sale->invoice_id,
                    'description' => ucfirst($sale->type).' sale',
                    'net' => (float) $sale->net_price,
                    'vat' => (float) $sale->vat_amount,
                    'gross' => (float) $sale->grand_total,
                ];
            }

            foreach ((clone $incomes)->orderBy('transactions.date')->get([
                'transactions.date', 'incomes.description',
                'incomes.net_amount', 'incomes.vat_amount', 'incomes.amount',
            ]) as $income) {
                $rows[] = [
                    'date' => $income->date,
                    'reference' => 'Income #'.$income->description,
                    'description' => 'Other income',
                    'net' => (float) $income->net_amount,
                    'vat' => (float) $income->vat_amount,
                    'gross' => (float) $income->amount,
                ];
            }

            foreach ((clone $returns)->orderBy('journal_entries.date')->get([
                'journal_entries.date as return_date',
                'sale_returns.id',
                'sale_returns.net_amount',
                'sale_returns.vat_amount',
                'sale_returns.refund_amount',
            ]) as $return) {
                $rows[] = [
                    'date' => $return->return_date,
                    'reference' => 'Return #'.$return->id,
                    'description' => 'Sale return / credit note',
                    'net' => -1 * (float) $return->net_amount,
                    'vat' => -1 * (float) $return->vat_amount,
                    'gross' => -1 * (float) $return->refund_amount,
                ];
            }
        }

        if (in_array($box, ['4', '7'], true)) {
            foreach ((clone $expenses)->orderBy('transactions.date')->get([
                'transactions.date', 'expenses.description',
                'expenses.net_amount', 'expenses.vat_amount', 'expenses.amount',
            ]) as $expense) {
                $rows[] = [
                    'date' => $expense->date,
                    'reference' => 'Expense #'.$expense->description,
                    'description' => 'Expense',
                    'net' => (float) $expense->net_amount,
                    'vat' => (float) $expense->vat_amount,
                    'gross' => (float) $expense->amount,
                ];
            }

            foreach ((clone $purchases)->orderBy('journal_entries.date')->get([
                'journal_entries.date', 'purchase_orders.order_number',
                'purchase_orders.net_amount', 'purchase_orders.vat_amount', 'purchase_orders.total_amount',
            ]) as $purchase) {
                $rows[] = [
                    'date' => $purchase->date,
                    'reference' => $purchase->order_number,
                    'description' => 'Purchase order',
                    'net' => (float) $purchase->net_amount,
                    'vat' => (float) $purchase->vat_amount,
                    'gross' => (float) $purchase->total_amount,
                ];
            }
        }

        usort($rows, fn (array $a, array $b) => strcmp((string) $a['date'], (string) $b['date']));

        return $rows;
    }

    /**
     * Output VAT breakdown by VAT rate for the period.
     *
     * Sales use the stored vat_breakdown because it is the authoritative split
     * for the whole document, including shipping and other non-stock lines.
     * Approved returns are then subtracted at their own rate, and non-sale
     * income (which also carries output VAT) is added.
     *
     * @return array<int, array<string, mixed>>
     */
    private function rateBreakdown(string $startDate, string $endDate): array
    {
        $sales = DB::table('sales')
            ->join('transactions', function ($join) {
                $join->on('sales.id', '=', 'transactions.fk_reference_id')
                    ->where('transactions.type', Transaction::TYPE_SALE_INCOME);
            })
            ->whereNull('sales.deleted_at')
            ->whereBetween('transactions.date', [$startDate, $endDate])
            ->get([
                'sales.id',
                'sales.vat_breakdown',
                'sales.net_price',
                'sales.vat_amount',
                'sales.grand_total',
            ]);

        $rows = [];

        foreach ($sales as $sale) {
            $bands = $this->decodeBreakdown($sale->vat_breakdown);

            if (empty($bands)) {
                // Legacy sales may predate the stored breakdown. Fall back to
                // the sale totals and infer the rate from the net/VAT split.
                $bands = [[
                    'code' => null,
                    'rate' => null,
                    'net' => (float) $sale->net_price,
                    'vat' => (float) $sale->vat_amount,
                    'gross' => (float) $sale->grand_total,
                ]];
            }

            foreach ($bands as $band) {
                $this->mergeBand(
                    $rows,
                    $band['code'] ?? null,
                    isset($band['rate']) ? (float) $band['rate'] : null,
                    (float) ($band['net'] ?? 0),
                    (float) ($band['vat'] ?? 0),
                    (float) ($band['gross'] ?? 0),
                );
            }
        }

        // Approved returns reduce output VAT, so net them against the matching rate.
        $returns = DB::table('sale_returns')
            ->join('journal_entries', 'sale_returns.journal_entry_id', '=', 'journal_entries.id')
            ->whereNull('sale_returns.deleted_at')
            ->whereNull('journal_entries.deleted_at')
            ->whereIn('sale_returns.status', ['approved', 'completed'])
            ->whereBetween('journal_entries.date', [$startDate, $endDate])
            ->get([
                'sale_returns.vat_code',
                'sale_returns.vat_rate',
                'sale_returns.net_amount',
                'sale_returns.vat_amount',
                'sale_returns.refund_amount',
            ]);

        foreach ($returns as $return) {
            $this->mergeBand(
                $rows,
                $return->vat_code,
                $return->vat_rate !== null ? (float) $return->vat_rate : null,
                -1 * (float) $return->net_amount,
                -1 * (float) $return->vat_amount,
                -1 * (float) $return->refund_amount,
            );
        }

        // Other income is part of box 1/6 too, so include it by VAT rate.
        $incomes = DB::table('incomes')
            ->join('transactions', 'incomes.fk_transaction_id', '=', 'transactions.id')
            ->whereNull('incomes.deleted_at')
            ->where(function ($query) {
                $query->whereNull('incomes.table_name')
                    ->orWhere('incomes.table_name', '!=', 'sales');
            })
            ->whereBetween('transactions.date', [$startDate, $endDate])
            ->get([
                'incomes.vat_code',
                'incomes.vat_rate',
                'incomes.net_amount',
                'incomes.vat_amount',
                'incomes.amount',
            ]);

        foreach ($incomes as $income) {
            $this->mergeBand(
                $rows,
                $income->vat_code,
                $income->vat_rate !== null ? (float) $income->vat_rate : null,
                (float) $income->net_amount,
                (float) $income->vat_amount,
                (float) $income->amount,
            );
        }

        usort($rows, fn (array $a, array $b) => $a['rate'] <=> $b['rate']);

        return array_values($rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function decodeBreakdown(mixed $breakdown): array
    {
        if (is_string($breakdown)) {
            $breakdown = json_decode($breakdown, true);
        }

        return is_array($breakdown) ? $breakdown : [];
    }

    /**
     * Merge a net/VAT/gross band into the rate rows, normalising legacy rows
     * that have no code or rate.
     *
     * @param  array<string, array<string, mixed>>  $rows
     */
    private function mergeBand(
        array &$rows,
        ?string $code,
        ?float $rate,
        float $net,
        float $vat,
        float $gross
    ): void {
        [$code, $rate] = $this->normaliseBand($code, $rate, $net, $vat);
        $key = $this->rateKey($code, $rate);

        if (! isset($rows[$key])) {
            $rows[$key] = [
                'code' => $code,
                'rate' => $rate,
                'net' => 0.0,
                'vat' => 0.0,
                'gross' => 0.0,
            ];
        }

        $rows[$key]['net'] = round($rows[$key]['net'] + $net, 2);
        $rows[$key]['vat'] = round($rows[$key]['vat'] + $vat, 2);
        $rows[$key]['gross'] = round($rows[$key]['gross'] + $gross, 2);
    }

    /**
     * @return array{0: string, 1: float}
     */
    private function normaliseBand(?string $code, ?float $rate, float $net, float $vat): array
    {
        if ($rate === null) {
            $rate = $net != 0.0 ? round(($vat / $net) * 100, 2) : 0.0;
        }

        $rate = round(max(0, $rate), 2);

        if ($code === null || $code === '') {
            $code = $this->vat->codeForRate($rate);
        }

        return [$code, $rate];
    }

    private function rateKey(?string $code, ?float $rate): string
    {
        return ($code ?? 'default').'|'.number_format((float) $rate, 2, '.', '');
    }
}
