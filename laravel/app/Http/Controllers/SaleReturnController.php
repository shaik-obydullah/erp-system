<?php

namespace App\Http\Controllers;

use App\Accounting\AccountingPoster;
use App\Models\Cashbook;
use App\Models\Configuration;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\SaleReturn;
use App\Models\Stock;
use App\Models\Transaction;
use App\Services\ActivityLogger;
use App\Services\VatCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleReturnController extends Controller
{
    public function index(Request $request)
    {
        $query = SaleReturn::with('sale', 'stock.product');

        if ($search = $request->input('search')) {
            $query->whereHas('sale', function ($q) use ($search) {
                $q->where('invoice_id', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $returns = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($returns);
        }

        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('sale-returns.index', compact('returns', 'currencySymbol'));
    }

    public function creditNote(SaleReturn $saleReturn)
    {
        $saleReturn->load('sale.customer', 'stock.product');
        $currencySymbol = Configuration::get('currency_symbol', '$');
        $company = [
            'name' => Configuration::get('company_name', config('app.name', 'ERP System')),
            'address' => Configuration::get('company_address', ''),
            'vat_number' => Configuration::get('company_vat_number', ''),
            'country' => Configuration::get('company_country', 'GB'),
        ];

        return view('sale-returns.credit-note', compact('saleReturn', 'currencySymbol', 'company'));
    }

    public function create()
    {
        $sales = Sale::orderBy('id', 'desc')->get();
        $stocks = Stock::where('status', 'active')
            ->where('quantity', '>', 0)
            ->with('product')
            ->get();

        return view('sale-returns.create', compact('sales', 'stocks'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fk_sale_id' => 'required|exists:sales,id',
            'fk_stock_id' => 'required|exists:stocks,id',
            'quantity' => 'required|integer|min:1',
            'refund_amount' => 'required|numeric|min:0',
            'reason' => 'required|in:damaged,wrong_item,customer_request,defective,other',
            'note' => 'nullable|string',
        ]);

        $calculated = $this->calculateVat($validated);

        $return = SaleReturn::create([
            ...$validated,
            'vat_code' => $calculated['code'],
            'vat_rate' => $calculated['rate'],
            'net_amount' => $calculated['net'],
            'vat_amount' => $calculated['vat'],
            'status' => 'pending',
            'created_by' => auth('admin')->id(),
        ]);

        ActivityLogger::created('Sale Return', $return);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Sale return created successfully.',
                'redirect' => route('sale-returns.index'),
            ]);
        }

        return redirect()->route('sale-returns.index')
            ->with('success', 'Sale return created successfully.');
    }

    public function edit(SaleReturn $saleReturn)
    {
        $saleReturn->load('sale', 'stock.product');
        $sales = Sale::orderBy('id', 'desc')->get();
        $stocks = Stock::where('status', 'active')
            ->with('product')
            ->get();

        return view('sale-returns.edit', compact('saleReturn', 'sales', 'stocks'));
    }

    public function update(Request $request, SaleReturn $saleReturn)
    {
        $validated = $request->validate([
            'fk_sale_id' => 'required|exists:sales,id',
            'fk_stock_id' => 'required|exists:stocks,id',
            'quantity' => 'required|integer|min:1',
            'refund_amount' => 'required|numeric|min:0',
            'reason' => 'required|in:damaged,wrong_item,customer_request,defective,other',
            'note' => 'nullable|string',
            'status' => 'required|in:pending,approved,rejected,completed',
        ]);

        $calculated = $this->calculateVat($validated);

        $saleReturn->update([
            ...$validated,
            'vat_code' => $calculated['code'],
            'vat_rate' => $calculated['rate'],
            'net_amount' => $calculated['net'],
            'vat_amount' => $calculated['vat'],
            'updated_by' => auth('admin')->id(),
        ]);

        ActivityLogger::updated('Sale Return', $saleReturn, $saleReturn->toArray());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Sale return updated successfully.',
                'redirect' => route('sale-returns.index'),
            ]);
        }

        return redirect()->route('sale-returns.index')
            ->with('success', 'Sale return updated successfully.');
    }

    public function destroy(Request $request, SaleReturn $saleReturn)
    {
        $adminId = auth('admin')->id();

        DB::transaction(function () use ($saleReturn, $adminId) {
            // Deleting a return must undo its refund/restock entries and the
            // cashbook/transaction rows that were created when it was approved.
            app(AccountingPoster::class)->reverseOperational('sale_return', $saleReturn->id, $adminId);

            $saleReturn->update(['deleted_by' => $adminId, 'journal_entry_id' => null]);
            $saleReturn->delete();
        });

        ActivityLogger::deleted('Sale Return', $saleReturn);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Sale return deleted successfully.',
                'redirect' => route('sale-returns.index'),
            ]);
        }

        return redirect()->route('sale-returns.index')
            ->with('success', 'Sale return deleted successfully.');
    }

    public function approve(Request $request, SaleReturn $saleReturn)
    {
        $adminId = auth('admin')->id();

        // Ensure legacy/pending returns carry a VAT split before the credit
        // note and ledger reversal are posted.
        if ((float) $saleReturn->refund_amount > 0) {
            $calculated = $this->calculateVat([
                'fk_sale_id' => $saleReturn->fk_sale_id,
                'fk_stock_id' => $saleReturn->fk_stock_id,
                'refund_amount' => $saleReturn->refund_amount,
            ]);

            if (round((float) $saleReturn->vat_amount, 2) !== round($calculated['vat'], 2)) {
                $saleReturn->update([
                    'vat_code' => $calculated['code'],
                    'vat_rate' => $calculated['rate'],
                    'net_amount' => $calculated['net'],
                    'vat_amount' => $calculated['vat'],
                ]);
            }
        }

        $saleReturn->update([
            'status' => 'approved',
            'updated_by' => $adminId,
        ]);

        $stock = Stock::find($saleReturn->fk_stock_id);
        if ($stock) {
            $stock->update([
                'quantity' => $stock->quantity + $saleReturn->quantity,
                'updated_by' => $adminId,
            ]);
        }

        // Accounting: record refund transaction
        $sale = Sale::find($saleReturn->fk_sale_id);
        if ($sale) {
            Transaction::create([
                'date' => now()->toDateString(),
                'type' => Transaction::TYPE_MISC_EXPENSE,
                'fk_reference_id' => $saleReturn->id,
                'amount' => $saleReturn->refund_amount,
                'paid_amount' => $saleReturn->refund_amount,
                'due_amount' => 0,
                'created_by' => $adminId,
            ]);

            // Cashbook: record refund outflow
            Cashbook::create([
                'table_name' => 'sale_returns',
                'fk_reference_id' => $saleReturn->id,
                'description' => "Refund for return #{$saleReturn->id} (Sale {$sale->invoice_id})",
                'in_amount' => 0,
                'out_amount' => $saleReturn->refund_amount,
                'amount_payable' => 0,
                'amount_receivable' => 0,
                'created_by' => $adminId,
            ]);

            app(AccountingPoster::class)->saleReturn($saleReturn, $sale, $adminId);

            // Update sale: reduce paid_amount and grand_total
            $sale->update([
                'paid_amount' => max(0, $sale->paid_amount - $saleReturn->refund_amount),
                'grand_total' => max(0, $sale->grand_total - $saleReturn->refund_amount),
                'updated_by' => $adminId,
            ]);
        }

        ActivityLogger::updated('Sale Return', $saleReturn, $saleReturn->toArray());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Sale return approved and item restocked.',
                'redirect' => route('sale-returns.index'),
            ]);
        }

        return redirect()->route('sale-returns.index')
            ->with('success', 'Sale return approved and item restocked.');
    }

    /**
     * Resolve the VAT treatment for a return, preferring the original sale
     * line's rate and falling back to the product's current rate.
     *
     * @param  array<string, mixed>  $data
     * @return array{code: string, rate: float, net: float, vat: float, gross: float}
     */
    private function calculateVat(array $data): array
    {
        $vat = app(VatCalculator::class);
        $code = null;
        $rate = null;

        $detail = SaleDetail::where('fk_sale_id', $data['fk_sale_id'])
            ->where('fk_stock_id', $data['fk_stock_id'])
            ->orderByDesc('id')
            ->first();

        if ($detail) {
            $code = $detail->vat_code;
            $rate = $detail->vat_rate !== null ? (float) $detail->vat_rate : null;
        }

        if ($code === null && $rate === null) {
            $stock = Stock::with('product')->find($data['fk_stock_id']);
            $treatment = $vat->forProduct($stock?->product);
            $code = $treatment['code'];
            $rate = $treatment['rate'];
        }

        return $vat->extract((float) $data['refund_amount'], $code, $rate);
    }

    public function reject(Request $request, SaleReturn $saleReturn)
    {
        $saleReturn->update([
            'status' => 'rejected',
            'updated_by' => auth('admin')->id(),
        ]);

        ActivityLogger::updated('Sale Return', $saleReturn, $saleReturn->toArray());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Sale return rejected.',
                'redirect' => route('sale-returns.index'),
            ]);
        }

        return redirect()->route('sale-returns.index')
            ->with('success', 'Sale return rejected.');
    }
}
