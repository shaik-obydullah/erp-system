<?php

namespace App\Console\Commands;

use App\Accounting\Reconciliation;
use App\Models\Expense;
use App\Models\FiscalPeriod;
use App\Models\Income;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AccountingVerify extends Command
{
    protected $signature = 'accounting:verify';

    protected $description = 'Verify that the double-entry ledger is balanced and reconciles to the simple layer';

    public function handle(): int
    {
        $problems = 0;

        $unbalanced = DB::table('journal_entries as e')
            ->join('journal_lines as l', 'l.journal_entry_id', '=', 'e.id')
            ->whereNull('e.deleted_at')
            ->where('e.status', 'posted')
            ->select('e.id', 'e.entry_no')
            ->groupBy('e.id', 'e.entry_no')
            ->havingRaw('ROUND(SUM(l.debit), 2) <> ROUND(SUM(l.credit), 2)')
            ->get();

        $this->line('Unbalanced journal entries: '.$unbalanced->count());
        foreach ($unbalanced as $entry) {
            $this->error("  {$entry->entry_no}");
            $problems++;
        }

        $totalDebit = (float) JournalLine::whereHas('entry', fn ($query) => $query->posted())->sum('debit');
        $totalCredit = (float) JournalLine::whereHas('entry', fn ($query) => $query->posted())->sum('credit');

        if (round($totalDebit, 2) === round($totalCredit, 2)) {
            $this->info("Trial balance: OK (debit {$totalDebit} = credit {$totalCredit})");
        } else {
            $this->error("Trial balance: FAIL (debit {$totalDebit} != credit {$totalCredit})");
            $problems++;
        }

        $reconciliation = app(Reconciliation::class)->summary();

        if ($reconciliation['cash_matched']) {
            $this->info("Cash reconciliation: OK ({$reconciliation['cash_account']})");
        } else {
            $this->warn('Cash reconciliation: difference of '.$reconciliation['cash_difference']
                ." (cashbook {$reconciliation['cashbook_net']} vs ledger {$reconciliation['cash_account']})");
        }

        if ($reconciliation['customer_matched']) {
            $this->info('Customer balances vs receivable: OK');
        } else {
            $this->warn('Customer balances vs receivable: difference of '.$reconciliation['receivable_difference']);
            $problems++;
        }

        if ($reconciliation['supplier_matched']) {
            $this->info('Supplier balances vs payable: OK');
        } else {
            $this->warn('Supplier balances vs payable: difference of '.$reconciliation['payable_difference']);
            $problems++;
        }

        if (abs($reconciliation['unassigned_receivable']) >= 0.01) {
            $this->line('Unassigned receivable (no customer on the source document): '.$reconciliation['unassigned_receivable']);
        }

        if (abs($reconciliation['unassigned_payable']) >= 0.01) {
            $this->line('Unassigned payable (no supplier on the source document): '.$reconciliation['unassigned_payable']);
        }

        $broken = JournalEntry::posted()
            ->withCount('lines')
            ->get()
            ->filter(fn ($entry) => $entry->lines_count < 2)
            ->count();

        if ($broken > 0) {
            $this->error("Journal entries with fewer than two lines: {$broken}");
            $problems++;
        }

        // No entry may fall inside a closed fiscal period.
        $closedEntries = 0;
        foreach (FiscalPeriod::closed()->get() as $period) {
            $closedEntries += JournalEntry::posted()
                ->whereDate('date', '>=', $period->start_date)
                ->whereDate('date', '<=', $period->end_date)
                ->count();
        }

        if ($closedEntries > 0) {
            $this->error("Journal entries dated in a closed fiscal period: {$closedEntries}");
            $problems++;
        } else {
            $this->info('Closed fiscal periods: OK');
        }

        // Source coverage: every simple-layer event should have an entry.
        $missing = [
            'sales' => max(0, Sale::count() - JournalEntry::where('event_type', 'saleIncome')->distinct()->count('source_id')),
            'sale dues' => max(0, Transaction::where('type', Transaction::TYPE_SALE_DUE)->count() - JournalEntry::where('event_type', 'saleDue')->count()),
            'sale returns' => max(0, SaleReturn::where('status', 'approved')->count() - JournalEntry::where('event_type', 'saleReturn')->count()),
            'incomes' => max(0, Income::where('table_name', '!=', 'sales')->count() - JournalEntry::where('event_type', 'income')->distinct()->count('source_id')),
            'expenses' => max(0, Expense::count() - JournalEntry::where('event_type', 'expense')->distinct()->count('source_id')),
        ];

        foreach ($missing as $label => $count) {
            if ($count === 0) {
                continue;
            }

            if (config('accounting.enforced')) {
                $this->error("Missing journal entries for {$label}: {$count}");
                $problems++;
            } else {
                $this->warn("Missing journal entries for {$label}: {$count} (shadow mode, not fatal)");
            }
        }

        if (array_sum($missing) === 0) {
            $this->info('Source coverage: OK');
        }

        return $problems === 0 ? self::SUCCESS : self::FAILURE;
    }
}
