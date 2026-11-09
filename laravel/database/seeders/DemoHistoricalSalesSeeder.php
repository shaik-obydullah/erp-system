<?php

namespace Database\Seeders;

use App\Accounting\AccountingPoster;
use App\Models\Cashbook;
use App\Models\Income;
use App\Models\JournalEntry;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Stock;
use App\Models\Transaction;
use App\Services\VatCalculator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds 11 months of backdated, completed demo sales before the current
 * month so the AI sales forecast has enough history (it needs at least 3
 * months with sales).
 *
 * The rows use the same VAT calculation, cashbook and double-entry posting
 * as a real POS sale, but they deliberately do NOT decrement current stock:
 * backdated demo sales must not change today's inventory.
 *
 * The invoice prefix makes the rows easy to find and remove later with
 * DemoHistoricalSalesCleanupSeeder.
 */
class DemoHistoricalSalesSeeder extends Seeder
{
    public const INVOICE_PREFIX = 'DEMO-HIST-';

    public const DESCRIPTION_PREFIX = 'Demo historical sale';

    public function run(): void
    {
        $adminId = 1;
        $months = 11;
        $start = now()->startOfMonth()->subMonthsNoOverflow($months);

        $stocks = Stock::where('status', 'active')
            ->where('quantity', '>', 0)
            ->with('product:id,name')
            ->orderBy('id')
            ->get();

        if ($stocks->isEmpty()) {
            $this->command?->warn('No active stocks found; skipping demo sales.');

            return;
        }

        $vat = app(VatCalculator::class);

        DB::transaction(function () use ($adminId, $months, $start, $stocks, $vat) {
            for ($i = 0; $i < $months; $i++) {
                $month = $start->copy()->addMonthsNoOverflow($i);
                $monthKey = $month->format('Y-m');

                if (Sale::where('invoice_id', 'like', self::INVOICE_PREFIX.$monthKey.'%')->exists()) {
                    $this->command?->warn("Skipped (exists): {$monthKey}");
                    continue;
                }

                // A gentle upward trend: 2 sales early, 4 by the latest months.
                $saleCount = 2 + intdiv($i, 4);
                $monthlyTotal = 0.0;

                for ($s = 1; $s <= $saleCount; $s++) {
                    $day = min(27, 5 + ($s * 7));
                    $date = $month->copy()->day($day)->toDateString();
                    $invoiceId = self::INVOICE_PREFIX.$monthKey.'-'.$s;

                    $lineCount = 1 + (($i + $s) % 2);
                    $picked = [];
                    $attempt = 0;
                    while (count($picked) < $lineCount && $attempt < $stocks->count()) {
                        $index = ($i * 7 + $s * 3 + $attempt * 5) % $stocks->count();
                        $stock = $stocks[$index];
                        $picked[$stock->id] = $stock;
                        $attempt++;
                    }

                    $lines = [];
                    foreach ($picked as $stock) {
                        $quantity = 1 + (($i + $s) % 2);
                        $gross = round((float) $stock->sale_price * $quantity, 2);
                        $treatment = $vat->forStock($stock);

                        $lines[] = $vat->line($gross, $treatment['code'], $treatment['rate']) + [
                            'quantity' => $quantity,
                            'stock_id' => $stock->id,
                            'stock_name' => $stock->product->name ?? '',
                            'cost_price' => (float) $stock->buy_price,
                            'total_stock' => (int) $stock->quantity,
                        ];
                    }

                    if (empty($lines)) {
                        continue;
                    }

                    $summary = $vat->summary($lines);
                    $grandTotal = round($summary['gross'], 2);
                    $buyPrice = round(array_sum(array_map(
                        fn (array $line) => (float) $line['cost_price'] * (int) $line['quantity'],
                        $lines
                    )), 2);

                    $monthlyTotal += $grandTotal;

                    $sale = Sale::create([
                        'table_name' => 'sales',
                        'fk_user_id' => null,
                        'invoice_id' => $invoiceId,
                        'type' => 'POS',
                        'net_price' => $summary['net'],
                        'vat_amount' => $summary['vat'],
                        'tax_amount' => 0,
                        'shipping_cost' => 0,
                        'discount_amount' => 0,
                        'grand_total' => $grandTotal,
                        'paid_amount' => $grandTotal,
                        'buy_price' => $buyPrice,
                        'sale_due' => 0,
                        'status' => 'completed',
                        'note' => self::DESCRIPTION_PREFIX,
                        'vat_breakdown' => $summary['breakdown'],
                        'created_by' => $adminId,
                    ]);

                    foreach ($lines as $line) {
                        SaleDetail::create([
                            'fk_sale_id' => $sale->id,
                            'fk_stock_id' => $line['stock_id'],
                            'stock_name' => $line['stock_name'],
                            'total_stock' => $line['total_stock'],
                            'sale_stock' => $line['quantity'],
                            'subtotal' => $line['gross'],
                            'cost_price' => $line['cost_price'],
                            'vat_code' => $line['code'],
                            'vat_rate' => $line['rate'],
                            'net_amount' => $line['net'],
                            'vat_amount' => $line['vat'],
                            'gross_amount' => $line['gross'],
                        ]);
                    }

                    $transaction = Transaction::create([
                        'date' => $date,
                        'type' => Transaction::TYPE_SALE_INCOME,
                        'fk_reference_id' => $sale->id,
                        'amount' => $grandTotal,
                        'paid_amount' => $grandTotal,
                        'due_amount' => 0,
                        'created_by' => $adminId,
                    ]);

                    Income::create([
                        'table_name' => 'sales',
                        'fk_transaction_id' => $transaction->id,
                        'description' => self::DESCRIPTION_PREFIX." #{$invoiceId}",
                        'amount' => $grandTotal,
                        'net_amount' => $summary['net'],
                        'vat_amount' => $summary['vat'],
                        'created_by' => $adminId,
                    ]);

                    Cashbook::create([
                        'table_name' => 'sales',
                        'fk_reference_id' => $sale->id,
                        'description' => self::DESCRIPTION_PREFIX." #{$invoiceId}",
                        'in_amount' => $grandTotal,
                        'out_amount' => 0,
                        'amount_payable' => 0,
                        'amount_receivable' => 0,
                        'created_by' => $adminId,
                    ]);

                    // Income ledger entry only (cogs = 0) so backdated demo
                    // sales do not touch current inventory or COGS.
                    app(AccountingPoster::class)->sale($sale, $grandTotal, 0, 0.0, $adminId);

                    // Sales have no timestamps, so the poster dates the entry
                    // with now(); move the demo entry to the sale date.
                    JournalEntry::where('source_type', 'sale')
                        ->where('source_id', $sale->id)
                        ->update(['date' => $date]);
                }

                $this->command?->info(sprintf(
                    '%s: %d sales, $%s',
                    $monthKey,
                    $saleCount,
                    number_format($monthlyTotal, 2)
                ));
            }
        });
    }
}
