<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Transaction;
use App\Services\OllamaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class AiController extends Controller
{
    protected OllamaService $ai;

    public function __construct(OllamaService $ai)
    {
        $this->ai = $ai;
    }

    public function productDescription(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'specs' => 'nullable|string',
        ]);

        $description = $this->ai->generateProductDescription(
            $validated['name'],
            $validated['category'] ?? '',
            $validated['specs'] ?? ''
        );

        if (is_null($description)) {
            return response()->json(['error' => 'Failed to generate product description'], 500);
        }

        return response()->json(['description' => $description]);
    }

    public function productSearch(Request $request)
    {
        $validated = $request->validate([
            'query' => 'required|string|max:500',
        ]);

        $products = Product::select('id', 'name', 'sku', 'fk_category_id')
            ->with(['stocks' => function ($q) {
                $q->select('fk_product_id', 'sale_price', 'quantity')
                    ->where('status', 'active');
            }])
            ->with('category:id,name')
            ->get()
            ->map(function ($product) {
                $activeStock = $product->stocks->first();
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => $activeStock ? (float) $activeStock->sale_price : 0,
                    'stock' => $activeStock ? $activeStock->quantity : 0,
                    'category' => $product->category->name ?? 'Uncategorized',
                ];
            })
            ->toArray();

        $response = $this->ai->searchProducts($validated['query'], $products);

        if (is_null($response)) {
            return response()->json(['error' => 'Failed to process search query'], 500);
        }

        return response()->json([
            'response' => $response,
            'products' => $products,
        ]);
    }

    public function inventoryInsights()
    {
        $stockData = Stock::where('status', 'active')
            ->with('product:id,name')
            ->select('id', 'fk_product_id', 'quantity', 'buy_price', 'sale_price', 'batch', 'status')
            ->get()
            ->map(function ($stock) {
                return [
                    'product' => $stock->product->name ?? 'Unknown',
                    'batch' => $stock->batch,
                    'quantity' => $stock->quantity,
                    'buy_price' => (float) $stock->buy_price,
                    'sale_price' => (float) $stock->sale_price,
                    'margin' => $stock->buy_price > 0
                        ? round((($stock->sale_price - $stock->buy_price) / $stock->buy_price) * 100, 1)
                        : 0,
                    'status' => $stock->status,
                ];
            })
            ->toArray();

        $insights = $this->ai->inventoryInsights($stockData);

        if (is_null($insights)) {
            return response()->json(['error' => 'Failed to generate inventory insights'], 500);
        }

        return response()->json(['insights' => $insights]);
    }

    public function salesForecast()
    {
        $twelveMonthsAgo = Carbon::now()->subMonths(12)->startOfMonth();

        // The sales table has no timestamps; the sale date lives on the
        // linked sale-income transaction. Collapse the transactions to one
        // row per sale before joining so a sale can never be counted twice
        // if a future flow posts more than one sale-income row for it.
        $saleDates = Transaction::query()
            ->where('type', Transaction::TYPE_SALE_INCOME)
            ->whereNull('deleted_at')
            ->where('date', '>=', $twelveMonthsAgo)
            ->selectRaw('fk_reference_id as sale_id, MIN(date) as sale_date')
            ->groupBy('fk_reference_id');

        $salesHistory = Sale::where('status', 'completed')
            ->joinSub($saleDates, 'sale_dates', 'sale_dates.sale_id', '=', 'sales.id')
            ->selectRaw('DATE_FORMAT(sale_dates.sale_date, "%Y-%m") as month, SUM(sales.grand_total) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();

        $allMonths = collect();
        for ($date = $twelveMonthsAgo->copy(); $date->lte(Carbon::now()); $date->addMonth()) {
            $allMonths->push($date->format('Y-m'));
        }

        $salesMap = collect($salesHistory)->pluck('total', 'month')->toArray();

        $formattedHistory = $allMonths->map(function ($month) use ($salesMap) {
            return [
                'month' => $month,
                'total' => (float) ($salesMap[$month] ?? 0),
            ];
        })->toArray();

        // A 0.5B model cannot forecast from one month of data without
        // inventing numbers, so answer deterministically until there is
        // enough history for a meaningful forecast.
        $monthsWithSales = collect($formattedHistory)
            ->filter(fn ($row) => $row['total'] > 0)
            ->values();

        if ($monthsWithSales->count() < 3) {
            $latest = $monthsWithSales->last();
            $forecast = $latest
                ? 'Not enough sales history for an AI forecast: only '
                    . $monthsWithSales->count()
                    . ' month(s) contain completed sales. Latest month ('
                    . $latest['month'] . '): $' . number_format($latest['total'], 2)
                    . '. A reliable 3-month forecast needs at least 3 months of data. '
                    . 'Next steps: align stock with recent demand and run a promotion to build more history.'
                : 'No completed sales were found in the last 12 months, so a forecast cannot be generated yet.';

            return response()->json(['forecast' => $forecast]);
        }

        $forecast = $this->ai->salesForecast($formattedHistory);

        if (is_null($forecast)) {
            return response()->json(['error' => 'Failed to generate sales forecast'], 500);
        }

        return response()->json(['forecast' => $forecast]);
    }

    public function customerSupport(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $keywords = array_filter(explode(' ', strtolower($validated['message'])));
        $context = [];

        if (!empty($keywords)) {
            $products = Product::where(function ($query) use ($keywords) {
                foreach ($keywords as $keyword) {
                    if (strlen($keyword) < 3) continue;
                    $query->orWhere('name', 'like', "%{$keyword}%");
                }
            })
            ->with(['stocks' => function ($q) {
                $q->where('status', 'active');
            }])
            ->limit(5)
            ->get()
            ->map(function ($product) {
                $activeStock = $product->stocks->first();
                return [
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => $activeStock ? (float) $activeStock->sale_price : 0,
                    'available' => $activeStock && $activeStock->quantity > 0,
                ];
            })
            ->toArray();

            if (!empty($products)) {
                $context['related_products'] = $products;
            }
        }

        $response = $this->ai->customerSupport($validated['message'], $context);

        if (is_null($response)) {
            return response()->json(['error' => 'Failed to generate response'], 500);
        }

        return response()->json(['response' => $response]);
    }

    public function priceSuggestion(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $product = Product::with(['stocks' => function ($q) {
            $q->where('status', 'active');
        }])->findOrFail($validated['product_id']);

        $activeStock = $product->stocks->first();
        $currentPrice = $activeStock ? (float) $activeStock->sale_price : 0;

        // The sales table has no timestamps; use the linked sale-income
        // transaction date. Aggregate the product's unit price from its sale
        // line (subtotal / quantity) rather than the whole-sale total.
        $recentSaleIds = Sale::where('status', 'completed')
            ->whereHas('transaction', function ($q) {
                $q->where('date', '>=', Carbon::now()->subMonths(3));
            })
            ->select('id');

        $recentSales = SaleDetail::whereIn('fk_sale_id', $recentSaleIds)
            ->whereHas('stock', function ($sq) use ($product) {
                $sq->where('fk_product_id', $product->id);
            })
            ->where('sale_stock', '>', 0)
            ->selectRaw('AVG(subtotal / sale_stock) as avg_price, MIN(subtotal / sale_stock) as min_price, MAX(subtotal / sale_stock) as max_price')
            ->first();

        $competitorPrices = [];
        if ($recentSales) {
            if ($recentSales->avg_price) {
                $competitorPrices[] = [
                    'source' => 'Average Sale (3 months)',
                    'price' => (float) $recentSales->avg_price,
                ];
            }
            if ($recentSales->min_price) {
                $competitorPrices[] = [
                    'source' => 'Lowest Sale (3 months)',
                    'price' => (float) $recentSales->min_price,
                ];
            }
            if ($recentSales->max_price) {
                $competitorPrices[] = [
                    'source' => 'Highest Sale (3 months)',
                    'price' => (float) $recentSales->max_price,
                ];
            }
        }

        $suggestion = $this->ai->suggestPrice($product->name, $currentPrice, $competitorPrices);

        if (is_null($suggestion)) {
            return response()->json(['error' => 'Failed to generate price suggestion'], 500);
        }

        return response()->json(['suggestion' => $suggestion]);
    }

    public function status()
    {
        return response()->json([
            'available' => $this->ai->isAvailable(),
            'model' => config('services.ollama.model', env('AI_MODEL', 'qwen2.5:0.5b')),
        ]);
    }
}
