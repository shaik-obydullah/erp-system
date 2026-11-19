<?php

namespace Database\Seeders;

use App\Accounting\AccountingPoster;
use App\Models\Cashbook;
use App\Models\Income;
use App\Models\Transaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds 11 months of backdated demo income before the current month so the
 * BI Prophet forecast has enough history (it requires at least 2 months).
 *
 * Rows are created through the same models/accounting poster used by
 * IncomeController, so cashbook and double-entry journal rows stay balanced.
 * The description prefix makes the demo rows easy to find and remove later.
 */
class DemoHistoricalIncomeSeeder extends Seeder
{
    public const DESCRIPTION_PREFIX = 'Demo historical income';

    public function run(): void
    {
        $adminId = 1;
        $months = 11;
        $start = now()->startOfMonth()->subMonthsNoOverflow($months);
        $seasonal = [1.00, 0.92, 1.06, 0.98, 1.03, 1.12, 1.08, 0.95, 1.00, 1.07, 1.15];

        DB::transaction(function () use ($start, $months, $seasonal, $adminId) {
            for ($i = 0; $i < $months; $i++) {
                $month = $start->copy()->addMonthsNoOverflow($i);
                $description = self::DESCRIPTION_PREFIX.' — '.$month->format('F Y');

                if (Income::where('description', $description)->exists()) {
                    $this->command?->warn("Skipped (exists): {$description}");
                    continue;
                }

                $amount = round(9000 * (1 + 0.035 * $i) * $seasonal[$i], 2);

                $transaction = Transaction::create([
                    'date' => $month->copy()->day(15)->toDateString(),
                    'type' => Transaction::TYPE_INCOME,
                    'fk_reference_id' => null,
                    'amount' => $amount,
                    'paid_amount' => $amount,
                    'due_amount' => 0,
                    'created_by' => $adminId,
                ]);

                $income = Income::create([
                    'table_name' => 'incomes',
                    'fk_transaction_id' => $transaction->id,
                    'description' => $description,
                    'amount' => $amount,
                    'vat_code' => 'zero',
                    'vat_rate' => 0,
                    'net_amount' => $amount,
                    'vat_amount' => 0,
                    'created_by' => $adminId,
                ]);

                Cashbook::create([
                    'table_name' => 'incomes',
                    'fk_reference_id' => $transaction->id,
                    'description' => $description,
                    'in_amount' => $amount,
                    'out_amount' => 0,
                    'amount_payable' => 0,
                    'amount_receivable' => 0,
                    'created_by' => $adminId,
                ]);

                app(AccountingPoster::class)->income($income, $adminId);

                $this->command?->info(sprintf('%s: $%s', $month->format('M Y'), number_format($amount, 2)));
            }
        });
    }
}
