<?php

namespace App\Console\Commands;

use App\Models\Sale;
use App\Models\SaleReturn;
use App\Services\SaleIncomeTracker;
use Illuminate\Console\Command;

class SyncSaleIncomes extends Command
{
    protected $signature = 'incomes:sync-sale-returns';

    protected $description = 'Recalculate sale income rows net of approved sale returns (idempotent)';

    public function handle(SaleIncomeTracker $tracker): int
    {
        $saleIds = SaleReturn::query()
            ->whereIn('status', ['approved', 'completed'])
            ->distinct()
            ->pluck('fk_sale_id')
            ->filter()
            ->values();

        if ($saleIds->isEmpty()) {
            $this->info('No approved sale returns found; nothing to sync.');

            return self::SUCCESS;
        }

        $updated = 0;
        $unchanged = 0;
        $missing = 0;

        foreach (Sale::query()->whereIn('id', $saleIds)->orderBy('id')->get() as $sale) {
            $income = $tracker->sync($sale);

            if (! $income) {
                $missing++;
                $this->warn("Sale #{$sale->id} ({$sale->invoice_id}): no linked income row, skipped.");

                continue;
            }

            if ($income->wasChanged()) {
                $updated++;
                $this->line(sprintf(
                    'Sale #%d (%s): income now %s (net %s, VAT %s)',
                    $sale->id,
                    $sale->invoice_id,
                    number_format((float) $income->amount, 2, '.', ''),
                    number_format((float) $income->net_amount, 2, '.', ''),
                    number_format((float) $income->vat_amount, 2, '.', ''),
                ));

                continue;
            }

            $unchanged++;
        }

        $this->info("Sale income sync complete: {$updated} updated, {$unchanged} unchanged, {$missing} missing.");

        return self::SUCCESS;
    }
}
