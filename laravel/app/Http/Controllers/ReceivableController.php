<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Configuration;
use App\Models\Customer;
use App\Models\JournalLine;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceivableController extends Controller
{
    public function index(Request $request)
    {
        $currencySymbol = Configuration::get('currency_symbol', '$');
        $arAccount = Account::where('system_key', 'accounts_receivable')->first();

        if (! $arAccount) {
            return view('receivables.index', [
                'receivables' => $this->emptyPaginator(),
                'customers' => collect(),
                'arAccount' => null,
                'ledgerTotal' => 0.0,
                'openTotal' => 0.0,
                'customerTotal' => 0.0,
                'unassignedTotal' => 0.0,
                'creditTotal' => 0.0,
                'difference' => 0.0,
                'reconciled' => true,
                'currencySymbol' => $currencySymbol,
            ]);
        }

        $documents = DB::query()->fromSub($this->documents($arAccount->id), 'ar')
            ->where(function ($query) {
                $query->where('ar.outstanding', '>', 0)
                    ->orWhere('ar.outstanding', '<', 0);
            });

        if ($search = $request->input('search')) {
            $matchingCustomers = Customer::withTrashed()
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->pluck('id');

            $documents->where(function ($query) use ($search, $matchingCustomers) {
                $query->where('ar.invoice_id', 'like', "%{$search}%")
                    ->orWhere('ar.memo', 'like', "%{$search}%")
                    ->orWhere('ar.entry_no', 'like', "%{$search}%")
                    ->orWhere('ar.source_type', 'like', "%{$search}%")
                    ->orWhereIn('ar.party_id', $matchingCustomers);
            });
        }

        $receivables = $documents->orderByDesc('ar.last_date')
            ->orderByDesc('ar.entry_id')
            ->paginate(15)
            ->withQueryString();

        $ledgerTotal = $this->ledgerBalance($arAccount->id);
        $openTotal = $this->sumOutstanding($arAccount->id, '>');
        $creditTotal = $this->sumOutstanding($arAccount->id, '<');
        $customerTotal = $this->sumOutstanding($arAccount->id, '>', true);
        $unassignedTotal = round($openTotal - $customerTotal, 2);
        $difference = round($ledgerTotal - ($openTotal + $creditTotal), 2);

        $partyIds = $receivables->getCollection()->pluck('party_id')->filter()->unique()->all();
        $customers = Customer::withTrashed()->whereIn('id', $partyIds)->get()->keyBy('id');

        if ($request->expectsJson()) {
            return response()->json($receivables);
        }

        return view('receivables.index', [
            'receivables' => $receivables,
            'customers' => $customers,
            'arAccount' => $arAccount,
            'ledgerTotal' => $ledgerTotal,
            'openTotal' => $openTotal,
            'customerTotal' => $customerTotal,
            'unassignedTotal' => $unassignedTotal,
            'creditTotal' => $creditTotal,
            'difference' => $difference,
            'reconciled' => abs($difference) < 0.01,
            'currencySymbol' => $currencySymbol,
        ]);
    }

    /**
     * Net every posted accounts-receivable movement per source document, so the
     * page follows the ledger instead of the legacy receivable table.
     */
    private function documents(int $accountId): Builder
    {
        return DB::table('journal_lines as l')
            ->join('journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->leftJoin('sales', function ($join) {
                $join->on('sales.id', '=', 'e.source_id')
                    ->where('e.source_type', '=', 'sale');
            })
            ->where('l.account_id', $accountId)
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
                MAX(sales.invoice_id) as invoice_id,
                ROUND(SUM(l.debit), 2) as total_debit,
                ROUND(SUM(l.credit), 2) as total_credit,
                ROUND(SUM(l.debit - l.credit), 2) as outstanding
            ')
            ->groupBy('e.source_type', DB::raw('COALESCE(e.source_id, -e.id)'));
    }

    private function ledgerBalance(int $accountId): float
    {
        return round((float) JournalLine::where('account_id', $accountId)
            ->whereHas('entry', fn ($query) => $query->posted())
            ->selectRaw('COALESCE(SUM(debit - credit), 0) as balance')
            ->value('balance'), 2);
    }

    private function sumOutstanding(int $accountId, string $sign, bool $customersOnly = false): float
    {
        $query = DB::query()
            ->fromSub($this->documents($accountId), 'ar')
            ->where('ar.outstanding', $sign, 0);

        if ($customersOnly) {
            $query->where('ar.party_type', 'customer')->whereNotNull('ar.party_id');
        }

        return round((float) $query->sum('ar.outstanding'), 2);
    }

    private function emptyPaginator()
    {
        return DB::query()->fromSub(
            DB::table('journal_lines')->whereRaw('1 = 0')->selectRaw('0 as outstanding'),
            'ar'
        )->paginate(15);
    }
}
