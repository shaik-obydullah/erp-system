<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Configuration;
use App\Models\Content;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use App\Models\Stock;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;

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
            ->withCount(['products' => fn($q) => $q->where('status', 'active')])
            ->orderBy('name')
            ->get()
            ->each(function ($cat) use ($childCategoryIds, $childProductCounts) {
                $childIds = $cat->children->pluck('id');
                $cat->products_count += $childProductCounts->only($childIds)->sum();
            });

        $products_count = Product::where('status', 'active')->count();

        $featuredProducts = Product::where('status', 'active')
            ->whereNotNull('fk_supplier_id')
            ->with(['stocks' => fn($q) => $q->where('status', 'active'), 'brand', 'category', 'supplier'])
            ->inRandomOrder()
            ->limit(8)
            ->get();

        $vendors = Supplier::where('status', 'active')
            ->orderBy('name')
            ->limit(6)
            ->get();

        $brands = Brand::where('status', 'active')
            ->withCount(['products' => fn($q) => $q->where('status', 'active')])
            ->orderBy('name')
            ->get();

        $newArrivals = Product::where('status', 'active')
            ->with(['stocks' => fn($q) => $q->where('status', 'active'), 'brand', 'supplier'])
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
            ->with(['stocks' => fn($q) => $q->where('status', 'active'), 'brand', 'category', 'supplier']);

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
            $query->whereHas('stocks', fn($q) => $q->where('sale_price', '>=', $minPrice));
        }

        if ($maxPrice = $request->input('max_price')) {
            $query->whereHas('stocks', fn($q) => $q->where('sale_price', '<=', $maxPrice));
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
                'stocks' => fn($q) => $q->where('status', 'active'),
                'brand', 'category', 'supplier', 'category.parent',
                'reviews' => fn($q) => $q->where('status', 'published')->orderBy('id', 'desc')->limit(10)->with('customer'),
            ])
            ->firstOrFail();

        $relatedProducts = Product::where('fk_category_id', $product->fk_category_id)
            ->where('id', '!=', $product->id)
            ->where('status', 'active')
            ->with(['stocks' => fn($q) => $q->where('status', 'active'), 'brand', 'supplier', 'reviews'])
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
            ->with(['stocks' => fn($q) => $q->where('status', 'active'), 'brand', 'category'])
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

    public function shippingOptions(Request $request)
    {
        $country = strtoupper((string) $request->input('country', ''));
        $subtotal = (float) $request->input('subtotal', 0);

        $zones = ShippingZone::with(['methods' => fn($q) => $q->where('status', 'active')->orderBy('sort_order')])
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get();

        $matchedZone = $zones->first(fn($zone) => $zone->matchesCountry($country));

        if (!$matchedZone) {
            return response()->json([
                'zone' => null,
                'methods' => [],
                'message' => 'No shipping options available for the selected country.',
            ]);
        }

        $methods = $matchedZone->methods
            ->filter(fn($method) => $method->isAvailable($subtotal))
            ->values()
            ->map(fn($method) => [
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
