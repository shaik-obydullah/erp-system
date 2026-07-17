<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Configuration;
use App\Models\Content;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Supplier;
use Illuminate\Http\Request;

class StorefrontController extends Controller
{
    protected $currencySymbol;

    public function __construct()
    {
        $this->currencySymbol = Configuration::get('currency_symbol', '$');
    }

    public function home()
    {
        $categories = Category::whereNull('fk_category_id')
            ->where('status', 'active')
            ->withCount(['products' => fn($q) => $q->where('status', 'active')])
            ->orderBy('name')
            ->get();

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
        $pageContent = Content::active()->type('page')->orderBy('sort_order')->get();
        $faqContent = Content::active()->type('faq')->orderBy('sort_order')->get();

        return view('storefront.home', compact(
            'categories', 'featuredProducts', 'vendors', 'brands', 'newArrivals',
            'products_count', 'heroContent', 'pageContent', 'faqContent'
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
                'reviews' => fn($q) => $q->where('status', 'published')->orderBy('id', 'desc')->limit(10),
            ])
            ->firstOrFail();

        $relatedProducts = Product::where('fk_category_id', $product->fk_category_id)
            ->where('id', '!=', $product->id)
            ->where('status', 'active')
            ->with(['stocks' => fn($q) => $q->where('status', 'active'), 'brand', 'supplier'])
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
        return view('storefront.checkout', ['currencySymbol' => $this->currencySymbol]);
    }
}
