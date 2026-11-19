<?php

namespace App\Console\Commands;

use App\Services\LedgerProjector;
use Illuminate\Console\Command;

class AccountingBackfill extends Command
{
    protected $signature = 'accounting:backfill
                            {--only=* : Only replay these groups (sales, dues, returns, incomes, expenses, funds, shipment_costs, fixed_assets, payrolls, campaign_spend, productions, opening_stock, supplier_openings, shipment_returns). Pass stock_adjustments explicitly to replay reviewed legacy rows.}
                            {--force : Run even when the ledger feature flag is disabled}';

    protected $description = 'Replay existing simple-layer rows into the double-entry ledger (idempotent)';

    public function handle(LedgerProjector $projector): int
    {
        if (! config('accounting.enabled')) {
            if (! $this->option('force')) {
                $this->error('The ledger is disabled. Re-run with --force or set ACCOUNTING_GL_ENABLED=true.');

                return self::FAILURE;
            }

            // Let the poster run for this console command only.
            config(['accounting.enabled' => true]);
        }

        $only = array_values(array_filter((array) $this->option('only')));
        $counts = $projector->run($only ?: null);

        foreach ($counts as $group => $count) {
            $this->line(sprintf('  %-10s %d row(s)', $group, $count));
        }

        $this->info('Backfill complete. Re-running is safe: existing entries are skipped by source key.');

        return self::SUCCESS;
    }
}
