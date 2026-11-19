<?php

namespace Database\Seeders;

use App\Models\Cashbook;
use App\Models\Income;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Transaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Removes the rows created by DemoHistoricalSalesSeeder, including their
 * details, cashbook, income and double-entry journal rows.
 */
class DemoHistoricalSalesCleanupSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $sales = Sale::withTrashed()
                ->where('invoice_id', 'like', DemoHistoricalSalesSeeder::INVOICE_PREFIX.'%')
                ->get();

            if ($sales->isEmpty()) {
                $this->command?->warn('No demo historical sales found.');

                return;
            }

            $saleIds = $sales->pluck('id');
            $detailCount = SaleDetail::withTrashed()->whereIn('fk_sale_id', $saleIds)->count();
            $transactionIds = Transaction::withTrashed()
                ->where('type', Transaction::TYPE_SALE_INCOME)
                ->whereIn('fk_reference_id', $saleIds)
                ->pluck('id');

            $journalIds = JournalEntry::withTrashed()
                ->where('source_type', 'sale')
                ->whereIn('source_id', $saleIds)
                ->pluck('id')
                ->merge(Income::withTrashed()->whereIn('fk_transaction_id', $transactionIds)->pluck('journal_entry_id'))
                ->merge(Transaction::withTrashed()->whereIn('id', $transactionIds)->pluck('journal_entry_id'))
                ->filter()
                ->unique()
                ->values();

            JournalLine::whereIn('journal_entry_id', $journalIds)->delete();
            JournalEntry::withTrashed()->whereIn('id', $journalIds)->forceDelete();

            Cashbook::withTrashed()
                ->where('table_name', 'sales')
                ->whereIn('fk_reference_id', $saleIds)
                ->forceDelete();

            Income::withTrashed()->whereIn('fk_transaction_id', $transactionIds)->forceDelete();
            Transaction::withTrashed()->whereIn('id', $transactionIds)->forceDelete();
            SaleDetail::withTrashed()->whereIn('fk_sale_id', $saleIds)->forceDelete();
            Sale::withTrashed()->whereIn('id', $saleIds)->forceDelete();

            $this->command?->info(sprintf(
                'Removed %d demo sales, %d details, %d transactions and %d journal entries.',
                $saleIds->count(),
                $detailCount,
                $transactionIds->count(),
                $journalIds->count()
            ));
        });
    }
}
