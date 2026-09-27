<?php

namespace App\Http\Controllers;

use App\Models\Cashbook;
use App\Models\Configuration;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Stock;
use App\Models\Transaction;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

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

        $return = SaleReturn::create([
            ...$validated,
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

        $saleReturn->update([
            ...$validated,
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
        $saleReturn->update(['deleted_by' => auth('admin')->id()]);
        ActivityLogger::deleted('Sale Return', $saleReturn);
        $saleReturn->delete();

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
