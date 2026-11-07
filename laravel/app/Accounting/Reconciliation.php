<?php

namespace App\Accounting;

use App\Models\Account;
use App\Models\Cashbook;
use App\Models\Customer;
use App\Models\JournalLine;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;

/**
 * Compares the simple layer (cashbook, party balances) with the double-entry
 * ledger. Party balances are split by sign because a positive customer balance
 * is an advance while a negative one is an amount the customer owes us.
 */
class Reconciliation
{
    /**
     * @return array<string, float|bool>
     */
    public function summary(): array
    {
        $cashbookNet = round((float) Cashbook::sum('in_amount') - (float) Cashbook::sum('out_amount'), 2);
        $cashAccount = $this->accountBalance('cash');

        $customerDue = $this->partyDue(Customer::query());
        $supplierDue = $this->partyDue(Supplier::query());

        $customerReceivable = $this->partyAccountBalance('accounts_receivable', 'customer');
        $supplierPayable = $this->partyAccountBalance('accounts_payable', 'supplier');

        $totalReceivable = $this->accountBalance('accounts_receivable');
        $totalPayable = $this->accountBalance('accounts_payable');

        $cashDifference = round($cashbookNet - $cashAccount, 2);
        $receivableDifference = round($customerDue - $customerReceivable, 2);
        $payableDifference = round($supplierDue - $supplierPayable, 2);

        return [
            'cashbook_net' => $cashbookNet,
            'cash_account' => $cashAccount,
            'cash_difference' => $cashDifference,
            'customer_due' => $customerDue,
            'customer_receivable' => $customerReceivable,
            'receivable_difference' => $receivableDifference,
            'supplier_due' => $supplierDue,
            'supplier_payable' => $supplierPayable,
            'payable_difference' => $payableDifference,
            // Receivables/payables without a party cannot be reconciled to a
            // subledger row (for example, a POS sale made without a customer).
            'unassigned_receivable' => round($totalReceivable - $customerReceivable, 2),
            'unassigned_payable' => round($totalPayable - $supplierPayable, 2),
            'cash_matched' => abs($cashDifference) < 0.01,
            'customer_matched' => abs($receivableDifference) < 0.01,
            'supplier_matched' => abs($payableDifference) < 0.01,
        ];
    }

    public function accountBalance(string $systemKey): float
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

        return round($account->normal_balance === 'debit' ? $debit - $credit : $credit - $debit, 2);
    }

    /**
     * Balance of party-attributed lines on a control account. This is the
     * subledger total that should match the sum of party balances.
     */
    public function partyAccountBalance(string $systemKey, string $partyType): float
    {
        $account = Account::where('system_key', $systemKey)->first();

        if (! $account) {
            return 0.0;
        }

        $row = JournalLine::where('account_id', $account->id)
            ->where('party_type', $partyType)
            ->whereNotNull('party_id')
            ->whereHas('entry', fn ($query) => $query->posted())
            ->selectRaw('COALESCE(SUM(debit), 0) as debit, COALESCE(SUM(credit), 0) as credit')
            ->first();

        $debit = (float) ($row->debit ?? 0);
        $credit = (float) ($row->credit ?? 0);

        return round($account->normal_balance === 'debit' ? $debit - $credit : $credit - $debit, 2);
    }

    private function partyDue(Builder $query): float
    {
        // Negative customer/supplier balances represent amounts owed by/to the
        // party; positive balances are advances and are reconciled separately.
        return round(abs((float) $query->where('balance', '<', 0)->sum('balance')), 2);
    }
}
