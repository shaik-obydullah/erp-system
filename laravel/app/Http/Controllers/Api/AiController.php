<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Sale;
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
            ->with('stocks', function ($q) {
                $q->select('fk_product_id', 'sale_price', 'quantity')
                    ->where('status', 'active');
            })
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

        $salesHistory = Sale::where('status', 'completed')
            ->where('created_at', '>=', $twelveMonthsAgo)
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(grand_total) as total')
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
            ->with('stocks', function ($q) {
                $q->where('status', 'active');
            })
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

        $product = Product::with('stocks', function ($q) {
            $q->where('status', 'active');
        })->findOrFail($validated['product_id']);

        $activeStock = $product->stocks->first();
        $currentPrice = $activeStock ? (float) $activeStock->sale_price : 0;

        $recentSales = Sale::where('status', 'completed')
            ->whereHas('details', function ($q) use ($product) {
                $q->whereHas('stock', function ($sq) use ($product) {
                    $sq->where('fk_product_id', $product->id);
                });
            })
            ->where('created_at', '>=', Carbon::now()->subMonths(3))
            ->selectRaw('AVG(grand_total) as avg_price, MIN(grand_total) as min_price, MAX(grand_total) as max_price')
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
            'model' => config('services.ollama.model', env('AI_MODEL', 'llama3.2')),
        ]);
    }
}
