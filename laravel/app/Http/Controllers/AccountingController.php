<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Cashbook;
use App\Models\Configuration;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountingController extends Controller
{
    public function index(Request $request)
    {
        $currencySymbol = $this->currency();

        $moneyIn = (float) Cashbook::sum('in_amount');
        $moneyOut = (float) Cashbook::sum('out_amount');
        $cashBalance = $this->accountBalance('cash');
        $receivable = $this->accountBalance('accounts_receivable');
        $payable = $this->accountBalance('accounts_payable');
        $taxPayable = $this->accountBalance('sales_tax_payable');

        $totalDebit = (float) JournalLine::whereHas('entry', fn ($query) => $query->posted())->sum('debit');
        $totalCredit = (float) JournalLine::whereHas('entry', fn ($query) => $query->posted())->sum('credit');
        $balanced = round($totalDebit, 2) === round($totalCredit, 2);

        $entries = JournalEntry::with('lines.account')->latest('id')->limit(10)->get();
        $entryCount = JournalEntry::count();
        $accountCount = Account::count();

        $unbalancedCount = DB::table('journal_entries as e')
            ->join('journal_lines as l', 'l.journal_entry_id', '=', 'e.id')
            ->whereNull('e.deleted_at')
            ->where('e.status', 'posted')
            ->groupBy('e.id')
            ->havingRaw('ROUND(SUM(l.debit), 2) <> ROUND(SUM(l.credit), 2)')
            ->get()
            ->count();

        $brokenCount = JournalEntry::posted()
            ->withCount('lines')
            ->get()
            ->filter(fn ($entry) => $entry->lines_count < 2)
            ->count();

        $cashDifference = round(($moneyIn - $moneyOut) - $cashBalance, 2);
        $receivableDifference = round((float) Customer::sum('balance') - $receivable, 2);
        $payableDifference = round((float) Supplier::sum('balance') - $payable, 2);

        $health = [
            'balanced' => $balanced && $unbalancedCount === 0,
            'unbalanced_entries' => $unbalancedCount,
            'broken_entries' => $brokenCount,
            'cash_difference' => $cashDifference,
            'receivable_difference' => $receivableDifference,
            'payable_difference' => $payableDifference,
        ];

        $health['healthy'] = $health['balanced']
            && $brokenCount === 0
            && abs($cashDifference) < 0.01
            && abs($receivableDifference) < 0.01
            && abs($payableDifference) < 0.01;

        return view('accounting.index', compact(
            'currencySymbol',
            'moneyIn',
            'moneyOut',
            'cashBalance',
            'receivable',
            'payable',
            'taxPayable',
            'totalDebit',
            'totalCredit',
            'balanced',
            'entries',
            'entryCount',
            'accountCount',
            'health'
        ));
    }

    public function journal(Request $request)
    {
        [$from, $to] = $this->period($request);

        $query = JournalEntry::with('lines.account')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('memo', 'like', "%{$search}%")
                    ->orWhere('entry_no', 'like', "%{$search}%");
            });
        }

        $entries = $query->orderByDesc('date')->orderByDesc('id')->paginate(20)->withQueryString();

        return view('accounting.journal', [
            'entries' => $entries,
            'from' => $from,
            'to' => $to,
            'currencySymbol' => $this->currency(),
        ]);
    }

    public function ledger(Request $request)
    {
        [$from, $to] = $this->period($request);

        $accounts = Account::orderBy('code')->get();
        $defaultAccount = $accounts->firstWhere('system_key', 'cash') ?? $accounts->first();
        $accountId = (int) ($request->input('account_id') ?: ($defaultAccount->id ?? 0));
        $account = $accounts->firstWhere('id', $accountId);

        $opening = 0.0;
        $lines = collect();
        $closing = 0.0;

        if ($account) {
            $opening = (float) JournalLine::where('account_id', $account->id)
                ->whereHas('entry', fn ($query) => $query->posted()->whereDate('date', '<', $from))
                ->selectRaw('COALESCE(SUM(debit - credit), 0) as balance')
                ->value('balance');

            $lines = JournalLine::with('entry')
                ->where('account_id', $account->id)
                ->whereHas('entry', fn ($query) => $query->posted()->whereDate('date', '>=', $from)->whereDate('date', '<=', $to))
                ->get()
                ->sortBy(fn ($line) => $line->entry->date->format('Y-m-d').'-'.str_pad((string) $line->id, 10, '0', STR_PAD_LEFT))
                ->values();

            $movement = $lines->sum(fn ($line) => (float) $line->debit - (float) $line->credit);
            $closing = $opening + $movement;
        }

        return view('accounting.ledger', [
            'accounts' => $accounts,
            'account' => $account,
            'lines' => $lines,
            'opening' => $opening,
            'closing' => $closing,
            'from' => $from,
            'to' => $to,
            'currencySymbol' => $this->currency(),
        ]);
    }

    public function trialBalance(Request $request)
    {
        [$from, $to] = $this->period($request);
        $balances = $this->balances($from, $to);

        $totalDebit = $balances->sum('debit');
        $totalCredit = $balances->sum('credit');

        return view('accounting.trial-balance', [
            'balances' => $balances,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'from' => $from,
            'to' => $to,
            'currencySymbol' => $this->currency(),
        ]);
    }

    public function profitLoss(Request $request)
    {
        [$from, $to] = $this->period($request);
        $balances = $this->balances($from, $to);

        $revenue = $balances->where('type', 'revenue')->map(function ($row) {
            $row->balance = (float) $row->credit - (float) $row->debit;
            return $row;
        });

        $expenses = $balances->where('type', 'expense')->map(function ($row) {
            $row->balance = (float) $row->debit - (float) $row->credit;
            return $row;
        });

        $totalRevenue = $revenue->sum('balance');
        $totalExpense = $expenses->sum('balance');

        return view('accounting.profit-loss', [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'totalRevenue' => $totalRevenue,
            'totalExpense' => $totalExpense,
            'netProfit' => $totalRevenue - $totalExpense,
            'from' => $from,
            'to' => $to,
            'currencySymbol' => $this->currency(),
        ]);
    }

    public function balanceSheet(Request $request)
    {
        $to = $request->input('to') ?: now()->toDateString();
        $balances = $this->balances('1970-01-01', $to);

        $assets = $balances->where('type', 'asset')->map(function ($row) {
            $row->balance = (float) $row->debit - (float) $row->credit;
            return $row;
        });

        $liabilities = $balances->where('type', 'liability')->map(function ($row) {
            $row->balance = (float) $row->credit - (float) $row->debit;
            return $row;
        });

        $equity = $balances->where('type', 'equity')->map(function ($row) {
            $row->balance = (float) $row->credit - (float) $row->debit;
            return $row;
        });

        $revenue = $balances->where('type', 'revenue')->sum(fn ($row) => (float) $row->credit - (float) $row->debit);
        $expense = $balances->where('type', 'expense')->sum(fn ($row) => (float) $row->debit - (float) $row->credit);
        $currentEarnings = $revenue - $expense;

        $totalAssets = $assets->sum('balance');
        $totalLiabilities = $liabilities->sum('balance');
        $totalEquity = $equity->sum('balance') + $currentEarnings;

        return view('accounting.balance-sheet', [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'currentEarnings' => $currentEarnings,
            'totalAssets' => $totalAssets,
            'totalLiabilities' => $totalLiabilities,
            'totalEquity' => $totalEquity,
            'to' => $to,
            'currencySymbol' => $this->currency(),
        ]);
    }

    public function cashFlow(Request $request)
    {
        [$from, $to] = $this->period($request);

        $cashAccounts = Account::whereIn('system_key', ['cash', 'bank'])->pluck('id')->all();

        $opening = (float) JournalLine::whereIn('account_id', $cashAccounts)
            ->whereHas('entry', fn ($query) => $query->posted()->whereDate('date', '<', $from))
            ->selectRaw('COALESCE(SUM(debit - credit), 0) as balance')
            ->value('balance');

        $rows = DB::table('journal_lines as l')
            ->join('journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->whereIn('l.account_id', $cashAccounts)
            ->where('e.status', 'posted')
            ->whereNull('e.deleted_at')
            ->whereDate('e.date', '>=', $from)
            ->whereDate('e.date', '<=', $to)
            ->groupBy('e.event_type')
            ->orderBy('e.event_type')
            ->select(
                'e.event_type',
                DB::raw('SUM(l.debit) as inflow'),
                DB::raw('SUM(l.credit) as outflow')
            )
            ->get()
            ->map(function ($row) {
                $row->inflow = (float) $row->inflow;
                $row->outflow = (float) $row->outflow;
                $row->net = $row->inflow - $row->outflow;
                $row->activity = $this->activityFor($row->event_type);

                return $row;
            });

        $groups = [
            'operating' => $rows->where('activity', 'operating'),
            'investing' => $rows->where('activity', 'investing'),
            'financing' => $rows->where('activity', 'financing'),
        ];

        $netMovement = $rows->sum('net');

        return view('accounting.cash-flow', [
            'groups' => $groups,
            'opening' => round($opening, 2),
            'netMovement' => round($netMovement, 2),
            'closing' => round($opening + $netMovement, 2),
            'from' => $from,
            'to' => $to,
            'currencySymbol' => $this->currency(),
        ]);
    }

    public function chartOfAccounts()
    {
        $accounts = Account::orderBy('code')->get()->groupBy('type');

        return view('accounting.chart-of-accounts', [
            'accounts' => $accounts,
            'currencySymbol' => $this->currency(),
        ]);
    }

    public function reconciliation()
    {
        $cashbookNet = (float) Cashbook::sum('in_amount') - (float) Cashbook::sum('out_amount');
        $cashAccount = $this->accountBalance('cash');

        $customerBalances = (float) Customer::sum('balance');
        $receivable = $this->accountBalance('accounts_receivable');

        $supplierBalances = (float) Supplier::sum('balance');
        $payable = $this->accountBalance('accounts_payable');

        return view('accounting.reconciliation', [
            'cashbookNet' => $cashbookNet,
            'cashAccount' => $cashAccount,
            'customerBalances' => $customerBalances,
            'receivable' => $receivable,
            'supplierBalances' => $supplierBalances,
            'payable' => $payable,
            'currencySymbol' => $this->currency(),
        ]);
    }

    private function activityFor(string $eventType): string
    {
        return match ($eventType) {
            'fixedAsset' => 'investing',
            'openingBalance', 'import' => 'financing',
            default => 'operating',
        };
    }

    private function balances(string $from, string $to)
    {
        return DB::table('journal_lines as l')
            ->join('journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->join('accounts as a', 'a.id', '=', 'l.account_id')
            ->where('e.status', 'posted')
            ->whereNull('e.deleted_at')
            ->whereNull('a.deleted_at')
            ->whereDate('e.date', '>=', $from)
            ->whereDate('e.date', '<=', $to)
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type', 'a.normal_balance')
            ->orderBy('a.code')
            ->select(
                'a.id',
                'a.code',
                'a.name',
                'a.type',
                'a.normal_balance',
                DB::raw('SUM(l.debit) as debit'),
                DB::raw('SUM(l.credit) as credit')
            )
            ->get();
    }

    private function accountBalance(string $systemKey): float
    {
        $account = Account::where('system_key', $systemKey)->first();

        if (! $account) {
            return 0.0;
        }

        $row = JournalLine::where('account_id', $account->id)
            ->whereHas('entry', fn ($query) => $query->posted())
            ->selectRaw('COALESCE(SUM(debit), 0) as debit, COALESCE(SUM(credit), 0) as credit')
            ->first();

        $debit = (float) ($row->debit ?? 0);
        $credit = (float) ($row->credit ?? 0);

        return round(in_array($account->normal_balance, ['debit'], true) ? $debit - $credit : $credit - $debit, 2);
    }

    private function currency(): string
    {
        return Configuration::get('currency_symbol', '$');
    }

    private function period(Request $request): array
    {
        $from = $request->input('from') ?: now()->startOfMonth()->toDateString();
        $to = $request->input('to') ?: now()->toDateString();

        return [$from, $to];
    }
}
