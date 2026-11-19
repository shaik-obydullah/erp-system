<?php

namespace App\Http\Controllers;

use App\Accounting\AccountingPoster;
use App\Models\Category;
use App\Models\Configuration;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $query = Stock::with(['product', 'warehouse']);

        if ($search = $request->input('search')) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($productId = $request->input('fk_product_id')) {
            $query->where('fk_product_id', $productId);
        }

        $stocks = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($stocks);
        }

        $products = Product::where('status', 'active')->orderBy('name')->get();
        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('stocks.index', compact('stocks', 'products', 'currencySymbol'));
    }

    public function create()
    {
        $products = Product::where('status', 'active')->orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();

        return view('stocks.create', compact('products', 'warehouses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fk_product_id' => 'required|exists:products,id',
            'fk_warehouses_id' => 'nullable|exists:warehouses,id',
            'batch' => 'nullable|string|max:100',
            'lot' => 'nullable|string|max:100',
            'quantity' => 'required|integer|min:0',
            'buy_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'vat_code' => 'nullable|in:standard,reduced,zero,exempt,outside_scope',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|in:active,inactive,archive',
        ]);

        $adminId = auth('admin')->id();

        $stock = DB::transaction(function () use ($validated, $adminId) {
            $stock = Stock::create([
                'fk_product_id' => $validated['fk_product_id'],
                'fk_warehouses_id' => $validated['fk_warehouses_id'] ?? null,
                'batch' => $validated['batch'] ?? null,
                'lot' => $validated['lot'] ?? null,
                'quantity' => $validated['quantity'],
                'buy_price' => $validated['buy_price'],
                'sale_price' => $validated['sale_price'],
                'vat_code' => $validated['vat_code'] ?? null,
                'vat_rate' => $validated['vat_rate'] ?? null,
                'status' => $validated['status'],
                'accounting_version' => 1,
                'created_by' => $adminId,
            ]);

            app(AccountingPoster::class)->stockIn($stock, $adminId);

            return $stock;
        });

        ActivityLogger::created('Stock', $stock);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Stock created successfully.',
                'redirect' => route('stocks.index'),
            ]);
        }

        return redirect()->route('stocks.index')
            ->with('success', 'Stock created successfully.');
    }

    public function edit(Stock $stock)
    {
        $products = Product::where('status', 'active')->orderBy('name')->get();

        if ($stock->product && ! $products->contains('id', $stock->fk_product_id)) {
            $products->push($stock->product);
        }

        $warehouses = Warehouse::orderBy('name')->get();

        return view('stocks.edit', compact('stock', 'products', 'warehouses'));
    }

    public function update(Request $request, Stock $stock)
    {
        $validated = $request->validate([
            'fk_product_id' => 'required|exists:products,id',
            'fk_warehouses_id' => 'nullable|exists:warehouses,id',
            'batch' => 'nullable|string|max:100',
            'lot' => 'nullable|string|max:100',
            'quantity' => 'required|integer|min:0',
            'buy_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'vat_code' => 'nullable|in:standard,reduced,zero,exempt,outside_scope',
            'vat_rate' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|in:active,inactive,archive',
        ]);

        $old = $stock->toArray();
        $adminId = auth('admin')->id();
        $valueChanged = (int) $stock->quantity !== (int) $validated['quantity']
            || round((float) $stock->buy_price, 2) !== round((float) $validated['buy_price'], 2);

        DB::transaction(function () use ($stock, $validated, $adminId, $valueChanged) {
            $poster = app(AccountingPoster::class);

            if ($valueChanged || (! $stock->journal_entry_id && (int) $validated['quantity'] > 0 && (float) $validated['buy_price'] > 0)) {
                $poster->reverseOperational('stock', $stock->id, $adminId);

                $stock->update([
                    'fk_product_id' => $validated['fk_product_id'],
                    'fk_warehouses_id' => $validated['fk_warehouses_id'] ?? null,
                    'batch' => $validated['batch'] ?? null,
                    'lot' => $validated['lot'] ?? null,
                    'quantity' => $validated['quantity'],
                    'buy_price' => $validated['buy_price'],
                    'sale_price' => $validated['sale_price'],
                    'vat_code' => $validated['vat_code'] ?? null,
                    'vat_rate' => $validated['vat_rate'] ?? null,
                    'status' => $validated['status'],
                    'journal_entry_id' => null,
                    'accounting_version' => max(1, (int) $stock->accounting_version) + 1,
                    'updated_by' => $adminId,
                ]);

                $poster->stockIn($stock->fresh(), $adminId);

                return;
            }

            $stock->update([
                'fk_product_id' => $validated['fk_product_id'],
                'fk_warehouses_id' => $validated['fk_warehouses_id'] ?? null,
                'batch' => $validated['batch'] ?? null,
                'lot' => $validated['lot'] ?? null,
                'quantity' => $validated['quantity'],
                'buy_price' => $validated['buy_price'],
                'sale_price' => $validated['sale_price'],
                'vat_code' => $validated['vat_code'] ?? null,
                'vat_rate' => $validated['vat_rate'] ?? null,
                'status' => $validated['status'],
                'updated_by' => $adminId,
            ]);
        });

        ActivityLogger::updated('Stock', $stock, $stock->toArray());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Stock updated successfully.',
                'redirect' => route('stocks.index'),
            ]);
        }

        return redirect()->route('stocks.index')
            ->with('success', 'Stock updated successfully.');
    }

    public function priceIncreaseForm()
    {
        $categories = Category::whereNull('fk_category_id')
            ->where('status', 'active')
            ->with(['children' => fn ($query) => $query->where('status', 'active')->orderBy('name')])
            ->orderBy('name')
            ->get();

        return view('stocks.price-increase', compact('categories'));
    }

    public function priceIncrease(Request $request)
    {
        $validated = $request->validate([
            'fk_category_id' => 'required|exists:categories,id',
            'percentage' => 'required|numeric|min:-100|max:100',
        ]);

        $category = Category::findOrFail($validated['fk_category_id']);
        $categoryIds = $this->categoryIdsIncludingChildren($category->id);
        $percentage = (float) $validated['percentage'];
        $adminId = auth('admin')->id();
        $updated = 0;

        DB::transaction(function () use ($categoryIds, $percentage, $adminId, &$updated) {
            Stock::whereHas('product', function ($query) use ($categoryIds) {
                $query->whereIn('fk_category_id', $categoryIds)
                    ->orWhereIn('fk_subcategory_id', $categoryIds);
            })->chunkById(200, function ($stocks) use ($percentage, $adminId, &$updated) {
                foreach ($stocks as $stock) {
                    $stock->update([
                        'sale_price' => max(0, round(((float) $stock->sale_price) * (1 + $percentage / 100), 2)),
                        'updated_by' => $adminId,
                    ]);

                    $updated++;
                }
            });
        });

        $direction = $percentage >= 0 ? 'increased' : 'decreased';
        $message = $updated > 0
            ? "Sale prices in {$category->name} {$direction} by ".abs($percentage)."% across {$updated} stock record(s)."
            : "No stock records found for category {$category->name}.";

        ActivityLogger::log(
            'Category Price Update',
            $updated > 0 ? 'success' : 'error',
            $message
        );

        if ($request->expectsJson()) {
            if ($updated === 0) {
                return response()->json(['message' => $message], 422);
            }

            return response()->json([
                'message' => $message,
                'redirect' => route('stocks.index'),
            ]);
        }

        return redirect()->route('stocks.index')
            ->with($updated > 0 ? 'success' : 'error', $message);
    }

    /**
     * Return the selected category plus all of its descendant category IDs.
     */
    private function categoryIdsIncludingChildren(int $categoryId): array
    {
        $ids = [$categoryId];
        $next = [$categoryId];

        while (! empty($next)) {
            $children = Category::whereIn('fk_category_id', $next)->pluck('id')->all();
            $newIds = array_values(array_diff($children, $ids));

            if (empty($newIds)) {
                break;
            }

            $ids = array_merge($ids, $newIds);
            $next = $newIds;
        }

        return $ids;
    }
}
