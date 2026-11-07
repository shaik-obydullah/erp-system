<?php

namespace App\Http\Controllers;

use App\Accounting\AccountingPoster;
use App\Models\Brand;
use App\Models\Cashbook;
use App\Models\Category;
use App\Models\Configuration;
use App\Models\Content;
use App\Models\Customer;
use App\Models\Income;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use App\Models\Stock;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Services\VatCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class StorefrontController extends Controller
{
    protected $currencySymbol;

    protected $freeShippingThreshold;

    public function __construct()
    {
        $this->currencySymbol = Configuration::get('currency_symbol', '$');
        $this->freeShippingThreshold = ShippingMethod::where('type', 'free_shipping')
            ->where('status', 'active')
            ->whereNotNull('min_order_amount')
            ->min('min_order_amount');
        View::share('freeShippingThreshold', $this->freeShippingThreshold);
    }

    public function home()
    {
        $childCategoryIds = Category::whereNull('fk_category_id')
            ->where('status', 'active')
            ->pluck('id');

        $childProductCounts = Product::where('status', 'active')
            ->whereIn('fk_category_id', $childCategoryIds)
            ->selectRaw('fk_category_id, count(*) as cnt')
            ->groupBy('fk_category_id')
            ->pluck('cnt', 'fk_category_id');

        $categories = Category::whereNull('fk_category_id')
            ->where('status', 'active')
            ->with('children')
            ->withCount(['products' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('name')
            ->get()
            ->each(function ($cat) use ($childProductCounts) {
                $childIds = $cat->children->pluck('id');
                $cat->products_count += $childProductCounts->only($childIds)->sum();
            });

        $products_count = Product::where('status', 'active')->count();

        $featuredProducts = Product::where('status', 'active')
            ->whereNotNull('fk_supplier_id')
            ->with(['stocks' => fn ($q) => $q->where('status', 'active'), 'brand', 'category', 'supplier'])
            ->inRandomOrder()
            ->limit(8)
            ->get();

        $vendors = Supplier::where('status', 'active')
            ->orderBy('name')
            ->limit(6)
            ->get();

        $brands = Brand::where('status', 'active')
            ->withCount(['products' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('name')
            ->get();

        $newArrivals = Product::where('status', 'active')
            ->with(['stocks' => fn ($q) => $q->where('status', 'active'), 'brand', 'supplier'])
            ->orderBy('id', 'desc')
            ->limit(8)
            ->get();

        $heroContent = Content::active()->type('hero')->orderBy('sort_order')->get();
        $sliderContent = Content::active()->type('slider')->orderBy('sort_order')->get();
        $pageContent = Content::active()->type('page')->orderBy('sort_order')->get();
        $faqContent = Content::active()->type('faq')->orderBy('sort_order')->get();

        return view('storefront.home', compact(
            'categories', 'featuredProducts', 'vendors', 'brands', 'newArrivals',
            'products_count', 'heroContent', 'sliderContent', 'pageContent', 'faqContent'
        ) + ['currencySymbol' => $this->currencySymbol]);
    }

    public function products(Request $request)
    {
        $query = Product::where('status', 'active')
            ->with(['stocks' => fn ($q) => $q->where('status', 'active'), 'brand', 'category', 'supplier']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($categoryId = $request->input('category')) {
            $query->where('fk_category_id', $categoryId);
        }

        if ($brandId = $request->input('brand')) {
            $query->where('fk_brand_id', $brandId);
        }

        if ($vendorId = $request->input('vendor')) {
            $query->where('fk_supplier_id', $vendorId);
        }

        if ($minPrice = $request->input('min_price')) {
            $query->whereHas('stocks', fn ($q) => $q->where('sale_price', '>=', $minPrice));
        }

        if ($maxPrice = $request->input('max_price')) {
            $query->whereHas('stocks', fn ($q) => $q->where('sale_price', '<=', $maxPrice));
        }

        $sort = $request->input('sort', 'newest');
        switch ($sort) {
            case 'price_low':
                $query->withSum('stocks as sale_price_sum', 'sale_price');
                $query->orderBy('sale_price_sum');
                break;
            case 'price_high':
                $query->withSum('stocks as sale_price_sum', 'sale_price');
                $query->orderByDesc('sale_price_sum');
                break;
            case 'popular':
                $query->orderByDesc('review_number');
                break;
            default:
                $query->orderBy('id', 'desc');
        }

        $products = $query->paginate(12)->withQueryString();

        $categories = Category::whereNull('fk_category_id')->where('status', 'active')->orderBy('name')->get();
        $brands = Brand::where('status', 'active')->orderBy('name')->get();

        return view('storefront.products', compact('products', 'categories', 'brands') + ['currencySymbol' => $this->currencySymbol]);
    }

    public function productDetail(string $slug)
    {
        $product = Product::where('url_slug', $slug)
            ->where('status', 'active')
            ->with([
                'stocks' => fn ($q) => $q->where('status', 'active'),
                'brand', 'category', 'supplier', 'category.parent',
                'reviews' => fn ($q) => $q->where('status', 'published')->orderBy('id', 'desc')->limit(10)->with('customer'),
            ])
            ->firstOrFail();

        $relatedProducts = Product::where('fk_category_id', $product->fk_category_id)
            ->where('id', '!=', $product->id)
            ->where('status', 'active')
            ->with(['stocks' => fn ($q) => $q->where('status', 'active'), 'brand', 'supplier', 'reviews'])
            ->limit(4)
            ->get();

        return view('storefront.product-detail', compact('product', 'relatedProducts') + ['currencySymbol' => $this->currencySymbol]);
    }

    public function vendorList()
    {
        $vendors = Supplier::where('status', 'active')
            ->orderBy('name')
            ->paginate(12);

        return view('storefront.vendors', compact('vendors'));
    }

    public function vendorRegister(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:suppliers,email',
            'password' => 'required|string|min:8|confirmed',
            'mobile' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
        ]);

        Supplier::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'mobile' => $validated['mobile'] ?? null,
            'address' => $validated['address'] ?? null,
            'balance' => 0,
            'status' => 'active',
        ]);

        return redirect()->route('store.vendors')
            ->with('vendor_success', 'Your vendor application has been submitted successfully!');
    }

    public function vendorStore(string $slug)
    {
        $vendor = Supplier::where('name', $slug)
            ->orWhere('id', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        $products = Product::where('fk_supplier_id', $vendor->id)
            ->where('status', 'active')
            ->with(['stocks' => fn ($q) => $q->where('status', 'active'), 'brand', 'category'])
            ->paginate(12);

        return view('storefront.vendor-store', compact('vendor', 'products') + ['currencySymbol' => $this->currencySymbol]);
    }

    public function cart()
    {
        return view('storefront.cart', ['currencySymbol' => $this->currencySymbol]);
    }

    public function checkout()
    {
        return view('storefront.checkout', [
            'currencySymbol' => $this->currencySymbol,
            'countries' => config('countries'),
        ]);
    }

    public function placeOrder(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:100',
            'customer_email' => 'required|email|max:100',
            'customer_phone' => 'nullable|string|max:20',
            'address' => 'required|string|max:255',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'required|string|size:2',
            'notes' => 'nullable|string',
            'payment_method' => 'nullable|string|max:30',
            'shipping_method_id' => 'nullable|exists:shipping_methods,id',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        $vat = app(VatCalculator::class);
        $lines = [];

        foreach ($validated['items'] as $item) {
            $product = Product::where('status', 'active')
                ->with(['stocks' => fn ($q) => $q->where('status', 'active')->where('quantity', '>', 0)])
                ->find($item['id']);

            if (! $product) {
                return response()->json(['message' => 'One of the products is no longer available.'], 422);
            }

            $stock = $product->stocks->first();

            if (! $stock || $stock->quantity < (int) $item['qty']) {
                return response()->json(['message' => "Insufficient stock for {$product->name}."], 422);
            }

            $treatment = $vat->forStock($stock);
            $quantity = (int) $item['qty'];
            $gross = round((float) $stock->sale_price * $quantity, 2);

            $lines[] = $vat->line($gross, $treatment['code'], $treatment['rate']) + [
                'quantity' => $quantity,
                'stock_id' => $stock->id,
                'stock_name' => $product->name,
                'cost_price' => (float) $stock->buy_price,
                'total_stock' => (int) $stock->quantity,
            ];
        }

        if (empty($lines)) {
            return response()->json(['message' => 'Your cart is empty.'], 422);
        }

        $shippingCost = 0.0;

        if (! empty($validated['shipping_method_id'])) {
            $method = ShippingMethod::where('status', 'active')->find($validated['shipping_method_id']);
            $subtotal = round(array_sum(array_column($lines, 'gross')), 2);

            if ($method) {
                $shippingCost = round((float) $method->calculateCost($subtotal), 2);
            }
        }

        if ($shippingCost > 0) {
            $lines[] = $vat->line($shippingCost, null, null) + [
                'description' => 'Shipping',
                'quantity' => 1,
                'stock_id' => null,
                'stock_name' => 'Shipping',
                'cost_price' => 0.0,
                'total_stock' => 0,
                'is_shipping' => true,
            ];
        }

        $summary = $vat->summary($lines);
        $grandTotal = round($summary['gross'], 2);
        $buyPrice = round(array_sum(array_map(
            fn (array $line) => (float) $line['cost_price'] * (int) $line['quantity'],
            $lines
        )), 2);

        DB::beginTransaction();

        try {
            $customer = Customer::firstOrCreate(
                ['email' => $validated['customer_email']],
                [
                    'name' => $validated['customer_name'],
                    'password' => Hash::make(Str::random(24)),
                    'phone' => $validated['customer_phone'] ?? null,
                    'address' => trim(implode(', ', array_filter([
                        $validated['address'],
                        $validated['city'] ?? null,
                        $validated['postal_code'] ?? null,
                    ]))),
                    'country' => strtoupper($validated['country']),
                    'balance' => 0,
                    'status' => 'active',
                ]
            );

            $prefix = Configuration::get('invoice_prefix', 'INV') ?: 'INV';
            $invoiceId = $prefix.'-'.strtoupper(Str::random(8)).'-'.time();

            $sale = Sale::create([
                'table_name' => 'sales',
                'fk_user_id' => $customer->id,
                'invoice_id' => $invoiceId,
                'type' => 'Ecommerce',
                'net_price' => $summary['net'],
                'vat_amount' => $summary['vat'],
                'tax_amount' => 0,
                'shipping_cost' => $shippingCost,
                'discount_amount' => 0,
                'grand_total' => $grandTotal,
                'paid_amount' => 0,
                'buy_price' => $buyPrice,
                'sale_due' => $grandTotal,
                'status' => 'completed',
                'note' => trim(($validated['notes'] ?? '').' '.($validated['payment_method'] ?? '')),
                'vat_breakdown' => $summary['breakdown'],
            ]);

            foreach ($lines as $line) {
                if (! empty($line['is_shipping']) || empty($line['stock_id'])) {
                    continue;
                }

                SaleDetail::create([
                    'fk_sale_id' => $sale->id,
                    'fk_stock_id' => $line['stock_id'],
                    'stock_name' => $line['stock_name'],
                    'total_stock' => $line['total_stock'],
                    'sale_stock' => $line['quantity'],
                    'subtotal' => $line['gross'],
                    'cost_price' => $line['cost_price'],
                    'vat_code' => $line['code'],
                    'vat_rate' => $line['rate'],
                    'net_amount' => $line['net'],
                    'vat_amount' => $line['vat'],
                    'gross_amount' => $line['gross'],
                ]);

                Stock::where('id', $line['stock_id'])->decrement('quantity', $line['quantity']);
            }

            $transaction = Transaction::create([
                'date' => now()->toDateString(),
                'type' => Transaction::TYPE_SALE_INCOME,
                'fk_reference_id' => $sale->id,
                'amount' => $grandTotal,
                'paid_amount' => 0,
                'due_amount' => $grandTotal,
            ]);

            Income::create([
                'table_name' => 'sales',
                'fk_transaction_id' => $transaction->id,
                'description' => "Order {$invoiceId}",
                'amount' => $grandTotal,
                'net_amount' => $summary['net'],
                'vat_amount' => $summary['vat'],
            ]);

            Cashbook::create([
                'table_name' => 'sales',
                'fk_reference_id' => $sale->id,
                'description' => "Due for {$invoiceId}",
                'in_amount' => 0,
                'out_amount' => 0,
                'amount_payable' => 0,
                'amount_receivable' => $grandTotal,
            ]);

            app(AccountingPoster::class)->sale($sale, 0, $grandTotal, $buyPrice);

            DB::commit();

            return response()->json([
                'message' => 'Order placed successfully.',
                'order_number' => $invoiceId,
                'sale_id' => $sale->id,
                'net_amount' => $summary['net'],
                'vat_amount' => $summary['vat'],
                'shipping' => $shippingCost,
                'total' => $grandTotal,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['message' => 'Unable to place order: '.$e->getMessage()], 500);
        }
    }

    public function shippingOptions(Request $request)
    {
        $country = strtoupper((string) $request->input('country', ''));
        $subtotal = (float) $request->input('subtotal', 0);

        $zones = ShippingZone::with(['methods' => fn ($q) => $q->where('status', 'active')->orderBy('sort_order')])
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get();

        $matchedZone = $zones->first(fn ($zone) => $zone->matchesCountry($country));

        if (! $matchedZone) {
            return response()->json([
                'zone' => null,
                'methods' => [],
                'message' => 'No shipping options available for the selected country.',
            ]);
        }

        $methods = $matchedZone->methods
            ->filter(fn ($method) => $method->isAvailable($subtotal))
            ->values()
            ->map(fn ($method) => [
                'id' => $method->id,
                'name' => $method->name,
                'type' => $method->type,
                'cost' => $method->calculateCost($subtotal),
                'min_order_amount' => $method->min_order_amount,
                'label' => $method->type === 'free_shipping'
                    ? 'Free'
                    : ($method->type === 'local_pickup' ? 'Free' : number_format($method->calculateCost($subtotal), 2)),
            ]);

        return response()->json([
            'zone' => ['id' => $matchedZone->id, 'name' => $matchedZone->name],
            'methods' => $methods,
        ]);
    }
}
