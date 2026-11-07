<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Configuration;
use App\Models\Customer;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerDueController extends Controller
{
    public function index(Request $request)
    {
        $currencySymbol = Configuration::get('currency_symbol', '$');
        $arAccount = Account::where('system_key', 'accounts_receivable')->first();

        if (! $arAccount) {
            return view('customers.due', [
                'sales' => Sale::query()->whereRaw('1 = 0')->paginate(15),
                'customers' => collect(),
                'arAccount' => null,
                'ledgerTotal' => 0.0,
                'customerDue' => 0.0,
                'unassignedDue' => 0.0,
                'pageTotal' => 0.0,
                'otherReceivable' => 0.0,
                'difference' => 0.0,
                'reconciled' => true,
                'currencySymbol' => $currencySymbol,
            ]);
        }

        $arBase = DB::table('journal_lines as l')
            ->join('journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->where('l.account_id', $arAccount->id)
            ->where('e.status', 'posted')
            ->whereNull('e.deleted_at');

        $ledgerTotal = $this->balance(clone $arBase);
        $customerDue = $this->balance(
            (clone $arBase)->where('l.party_type', 'customer')->whereNotNull('l.party_id')
        );
        $unassignedDue = round($ledgerTotal - $customerDue, 2);

        // Net every AR movement per sale so the page follows the ledger rather
        // than the sale's stored due column.
        $saleAr = DB::table('journal_lines as l')
            ->join('journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->join('sales', 'sales.id', '=', 'e.source_id')
            ->where('l.account_id', $arAccount->id)
            ->where('e.status', 'posted')
            ->whereNull('e.deleted_at')
            ->where('e.source_type', 'sale')
            ->whereNotNull('e.source_id')
            ->whereNull('sales.deleted_at')
            ->selectRaw('e.source_id as sale_id, SUM(l.debit - l.credit) as due, MAX(l.party_id) as party_id')
            ->groupBy('e.source_id')
            ->havingRaw('ROUND(SUM(l.debit - l.credit), 2) > 0');

        $query = Sale::query()
            ->joinSub($saleAr, 'ar', 'ar.sale_id', '=', 'sales.id')
            ->with('customer')
            ->select('sales.*', 'ar.due as ledger_due', 'ar.party_id as party_id');

        if ($search = $request->input('search')) {
            $matchingCustomers = Customer::withTrashed()
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->pluck('id');

            $query->where(function ($q) use ($search, $matchingCustomers) {
                $q->where('sales.invoice_id', 'like', "%{$search}%")
                    ->orWhereIn('sales.fk_user_id', $matchingCustomers)
                    ->orWhereIn('ar.party_id', $matchingCustomers);
            });
        }

        $sales = $query->orderByDesc('ar.due')
            ->orderByDesc('sales.id')
            ->paginate(15)
            ->withQueryString();

        $partyIds = $sales->getCollection()->pluck('party_id')->filter()->unique()->all();
        $customers = Customer::withTrashed()->whereIn('id', $partyIds)->get()->keyBy('id');

        $saleSourcedTotal = $this->balance(
            (clone $arBase)->where('e.source_type', 'sale')->whereNotNull('e.source_id')
        );
        $pageTotal = round((float) DB::query()->fromSub($saleAr, 'ar')->sum('due'), 2);
        $otherReceivable = round($ledgerTotal - $saleSourcedTotal, 2);
        $difference = round($saleSourcedTotal - $pageTotal, 2);

        return view('customers.due', [
            'sales' => $sales,
            'customers' => $customers,
            'arAccount' => $arAccount,
            'ledgerTotal' => $ledgerTotal,
            'customerDue' => $customerDue,
            'unassignedDue' => $unassignedDue,
            'pageTotal' => $pageTotal,
            'otherReceivable' => $otherReceivable,
            'difference' => $difference,
            'reconciled' => abs($difference) < 0.01,
            'currencySymbol' => $currencySymbol,
        ]);
    }

    private function balance($query): float
    {
        return round((float) $query
            ->selectRaw('COALESCE(SUM(l.debit - l.credit), 0) as balance')
            ->value('balance'), 2);
    }
}
