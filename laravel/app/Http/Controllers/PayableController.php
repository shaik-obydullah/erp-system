<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Configuration;
use App\Models\JournalLine;
use App\Models\Supplier;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayableController extends Controller
{
    public function index(Request $request)
    {
        $currencySymbol = Configuration::get('currency_symbol', '$');
        $apAccount = Account::where('system_key', 'accounts_payable')->first();
        $advanceAccount = Account::where('system_key', 'supplier_advances')->first();

        if (! $apAccount || ! $advanceAccount) {
            return view('payables.index', [
                'payables' => $this->emptyPaginator(),
                'suppliers' => collect(),
                'apAccount' => null,
                'advanceAccount' => null,
                'payableLedger' => 0.0,
                'advanceLedger' => 0.0,
                'openPayables' => 0.0,
                'openAdvances' => 0.0,
                'supplierPayables' => 0.0,
                'unassignedPayables' => 0.0,
                'difference' => 0.0,
                'reconciled' => true,
                'currencySymbol' => $currencySymbol,
            ]);
        }

        $documents = DB::query()->fromSub($this->documents($apAccount->id, $advanceAccount->id), 'ap')
            ->where(function ($query) {
                $query->where('ap.payable', '!=', 0)
                    ->orWhere('ap.advance', '!=', 0);
            });

        if ($search = $request->input('search')) {
            $matchingSuppliers = Supplier::withTrashed()
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->pluck('id');

            $documents->where(function ($query) use ($search, $matchingSuppliers) {
                $query->where('ap.order_number', 'like', "%{$search}%")
                    ->orWhere('ap.memo', 'like', "%{$search}%")
                    ->orWhere('ap.entry_no', 'like', "%{$search}%")
                    ->orWhere('ap.source_type', 'like', "%{$search}%")
                    ->orWhereIn('ap.party_id', $matchingSuppliers);
            });
        }

        $payables = $documents->orderByDesc('ap.last_date')
            ->orderByDesc('ap.entry_id')
            ->paginate(15)
            ->withQueryString();

        $payableLedger = $this->ledgerBalance($apAccount->id, true);
        $advanceLedger = $this->ledgerBalance($advanceAccount->id);
        $openPayables = $this->sumDocuments($apAccount->id, $advanceAccount->id, 'payable', '>');
        $openAdvances = $this->sumDocuments($apAccount->id, $advanceAccount->id, 'advance', '>');
        $supplierPayables = $this->sumDocuments($apAccount->id, $advanceAccount->id, 'payable', '>', true);
        $unassignedPayables = round($openPayables - $supplierPayables, 2);
        $difference = round(($payableLedger - $openPayables) + ($advanceLedger - $openAdvances), 2);

        $partyIds = $payables->getCollection()->pluck('party_id')->filter()->unique()->all();
        $suppliers = Supplier::withTrashed()->whereIn('id', $partyIds)->get()->keyBy('id');

        if ($request->expectsJson()) {
            return response()->json($payables);
        }

        return view('payables.index', [
            'payables' => $payables,
            'suppliers' => $suppliers,
            'apAccount' => $apAccount,
            'advanceAccount' => $advanceAccount,
            'payableLedger' => $payableLedger,
            'advanceLedger' => $advanceLedger,
            'openPayables' => $openPayables,
            'openAdvances' => $openAdvances,
            'supplierPayables' => $supplierPayables,
            'unassignedPayables' => $unassignedPayables,
            'difference' => $difference,
            'reconciled' => abs($difference) < 0.01,
            'currencySymbol' => $currencySymbol,
        ]);
    }

    /**
     * Net every posted payable and supplier-advance movement per source
     * document, so the page follows the ledger instead of the legacy payable
     * table.
     */
    private function documents(int $apAccountId, int $advanceAccountId): Builder
    {
        return DB::table('journal_lines as l')
            ->join('journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->leftJoin('purchase_orders as po', function ($join) {
                $join->on('po.id', '=', 'e.source_id')
                    ->where('e.source_type', '=', 'purchase_order');
            })
            ->whereIn('l.account_id', [$apAccountId, $advanceAccountId])
            ->where('e.status', 'posted')
            ->whereNull('e.deleted_at')
            ->selectRaw('
                e.source_type,
                MAX(e.source_id) as source_id,
                MIN(e.id) as entry_id,
                MAX(e.entry_no) as entry_no,
                MAX(e.date) as last_date,
                MAX(e.memo) as memo,
                MAX(l.description) as description,
                MAX(l.party_type) as party_type,
                MAX(l.party_id) as party_id,
                MAX(po.order_number) as order_number,
                ROUND(SUM(CASE WHEN l.account_id = ? THEN l.credit - l.debit ELSE 0 END), 2) as payable,
                ROUND(SUM(CASE WHEN l.account_id = ? THEN l.debit - l.credit ELSE 0 END), 2) as advance
            ', [$apAccountId, $advanceAccountId])
            ->groupBy('e.source_type', DB::raw('COALESCE(e.source_id, -e.id)'));
    }

    private function ledgerBalance(int $accountId, bool $creditNormal = false): float
    {
        $expression = $creditNormal
            ? 'COALESCE(SUM(credit - debit), 0)'
            : 'COALESCE(SUM(debit - credit), 0)';

        return round((float) JournalLine::where('account_id', $accountId)
            ->whereHas('entry', fn ($query) => $query->posted())
            ->selectRaw("{$expression} as balance")
            ->value('balance'), 2);
    }

    private function sumDocuments(int $apAccountId, int $advanceAccountId, string $column, string $sign, bool $suppliersOnly = false): float
    {
        $query = DB::query()
            ->fromSub($this->documents($apAccountId, $advanceAccountId), 'ap')
            ->where("ap.{$column}", $sign, 0);

        if ($suppliersOnly) {
            $query->where('ap.party_type', 'supplier')->whereNotNull('ap.party_id');
        }

        return round((float) $query->sum("ap.{$column}"), 2);
    }

    private function emptyPaginator()
    {
        return DB::query()->fromSub(
            DB::table('journal_lines')->whereRaw('1 = 0')->selectRaw('0 as payable, 0 as advance'),
            'ap'
        )->paginate(15);
    }
}
