<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Configuration;
use App\Models\JournalLine;
use App\Models\Supplier;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierDueController extends Controller
{
    public function index(Request $request)
    {
        $currencySymbol = Configuration::get('currency_symbol', '$');
        $apAccount = Account::where('system_key', 'accounts_payable')->first();
        $advanceAccount = Account::where('system_key', 'supplier_advances')->first();

        if (! $apAccount || ! $advanceAccount) {
            return view('suppliers.due', [
                'suppliers' => Supplier::query()->whereRaw('1 = 0')->paginate(15),
                'apAccount' => null,
                'advanceAccount' => null,
                'ledgerPayable' => 0.0,
                'ledgerAdvances' => 0.0,
                'supplierPayable' => 0.0,
                'unassignedPayable' => 0.0,
                'ledgerPosition' => 0.0,
                'operationalPosition' => 0.0,
                'difference' => 0.0,
                'reconciled' => true,
                'currencySymbol' => $currencySymbol,
            ]);
        }

        $positions = $this->positions($apAccount->id, $advanceAccount->id);

        $query = Supplier::query()
            ->withTrashed()
            ->joinSub($positions, 'pos', 'pos.supplier_id', '=', 'suppliers.id')
            ->select('suppliers.*', 'pos.payable as ledger_payable', 'pos.advance as ledger_advance')
            ->where(function ($q) {
                $q->where('pos.payable', '!=', 0)
                    ->orWhere('pos.advance', '!=', 0);
            });

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('suppliers.name', 'like', "%{$search}%")
                    ->orWhere('suppliers.email', 'like', "%{$search}%")
                    ->orWhere('suppliers.mobile', 'like', "%{$search}%");
            });
        }

        $suppliers = $query->orderByDesc('pos.payable')
            ->orderBy('suppliers.name')
            ->paginate(15)
            ->withQueryString();

        $ledgerPayable = $this->accountBalance($apAccount->id, true);
        $ledgerAdvances = $this->accountBalance($advanceAccount->id);
        $supplierPayable = $this->positionSum($positions, 'payable');
        $supplierAdvances = $this->positionSum($positions, 'advance');
        $unassignedPayable = round($ledgerPayable - $supplierPayable, 2);
        $ledgerPosition = round($supplierAdvances - $supplierPayable, 2);

        // The suppliers.balance column is the operational mirror of the ledger
        // position (advances positive, amounts owed negative). Comparing the two
        // flags any balance change that never reached accounting.
        $operationalPosition = round((float) Supplier::withTrashed()->sum('balance'), 2);
        $difference = round($ledgerPosition - $operationalPosition, 2);

        if ($request->expectsJson()) {
            return response()->json($suppliers);
        }

        return view('suppliers.due', [
            'suppliers' => $suppliers,
            'apAccount' => $apAccount,
            'advanceAccount' => $advanceAccount,
            'ledgerPayable' => $ledgerPayable,
            'ledgerAdvances' => $ledgerAdvances,
            'supplierPayable' => $supplierPayable,
            'unassignedPayable' => $unassignedPayable,
            'ledgerPosition' => $ledgerPosition,
            'operationalPosition' => $operationalPosition,
            'difference' => $difference,
            'reconciled' => abs($difference) < 0.01,
            'currencySymbol' => $currencySymbol,
        ]);
    }

    /**
     * Net every posted payable and advance movement per supplier, so the page
     * follows the accounting ledger instead of the suppliers.balance column.
     */
    private function positions(int $apAccountId, int $advanceAccountId): Builder
    {
        return DB::table('journal_lines as l')
            ->join('journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->whereIn('l.account_id', [$apAccountId, $advanceAccountId])
            ->where('e.status', 'posted')
            ->whereNull('e.deleted_at')
            ->where('l.party_type', 'supplier')
            ->whereNotNull('l.party_id')
            ->selectRaw('
                l.party_id as supplier_id,
                ROUND(SUM(CASE WHEN l.account_id = ? THEN l.credit - l.debit ELSE 0 END), 2) as payable,
                ROUND(SUM(CASE WHEN l.account_id = ? THEN l.debit - l.credit ELSE 0 END), 2) as advance
            ', [$apAccountId, $advanceAccountId])
            ->groupBy('l.party_id');
    }

    private function accountBalance(int $accountId, bool $creditNormal = false): float
    {
        $expression = $creditNormal
            ? 'COALESCE(SUM(credit - debit), 0)'
            : 'COALESCE(SUM(debit - credit), 0)';

        return round((float) JournalLine::where('account_id', $accountId)
            ->whereHas('entry', fn ($query) => $query->posted())
            ->selectRaw("{$expression} as balance")
            ->value('balance'), 2);
    }

    private function positionSum(Builder $positions, string $column): float
    {
        return round((float) DB::query()
            ->fromSub($positions, 'pos')
            ->selectRaw("COALESCE(SUM(pos.{$column}), 0) as total")
            ->value('total'), 2);
    }
}
