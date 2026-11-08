<?php

namespace Database\Seeders;

use App\Models\Cashbook;
use App\Models\Income;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Transaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Removes the rows created by DemoHistoricalIncomeSeeder, including their
 * cashbook and double-entry journal rows, so the demo data can be undone
 * without leaving an unbalanced ledger behind.
 */
class DemoHistoricalIncomeCleanupSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $incomes = Income::where('description', 'like', DemoHistoricalIncomeSeeder::DESCRIPTION_PREFIX.'%')->get();

            if ($incomes->isEmpty()) {
                $this->command?->warn('No demo historical income rows found.');
                return;
            }

            $transactionIds = $incomes->pluck('fk_transaction_id')->filter()->unique()->values();
            $journalIds = $incomes->pluck('journal_entry_id')
                ->merge(Transaction::whereIn('id', $transactionIds)->pluck('journal_entry_id'))
                ->filter()
                ->unique()
                ->values();

            JournalLine::whereIn('journal_entry_id', $journalIds)->delete();
            JournalEntry::whereIn('id', $journalIds)->delete();
            Cashbook::where('table_name', 'incomes')->whereIn('fk_reference_id', $transactionIds)->delete();
            Income::whereIn('id', $incomes->pluck('id'))->forceDelete();
            Transaction::whereIn('id', $transactionIds)->forceDelete();

            $this->command?->info(sprintf(
                'Removed %d demo incomes, %d transactions and %d journal entries.',
                $incomes->count(),
                $transactionIds->count(),
                $journalIds->count()
            ));
        });
    }
}
