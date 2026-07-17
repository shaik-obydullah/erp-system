<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Configuration;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['brand', 'category', 'supplier', 'unit', 'stocks' => function ($q) {
            $q->where('status', 'active');
        }]);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($brandId = $request->input('fk_brand_id')) {
            $query->where('fk_brand_id', $brandId);
        }

        if ($categoryId = $request->input('fk_category_id')) {
            $query->where('fk_category_id', $categoryId);
        }

        if ($supplierId = $request->input('fk_supplier_id')) {
            $query->where('fk_supplier_id', $supplierId);
        }

        $products = $query->orderBy('name')->paginate(15)->withQueryString();

        $products->each(function ($product) {
            $product->stock_quantity = $product->stocks->sum('quantity');
        });

        if ($request->expectsJson()) {
            return response()->json($products);
        }

        $brands = Brand::where('status', 'active')->orderBy('name')->get();
        $categories = Category::whereNull('fk_category_id')->where('status', 'active')->get();
        $suppliers = Supplier::where('status', 'active')->orderBy('name')->get();
        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('products.index', compact('products', 'brands', 'categories', 'suppliers', 'currencySymbol'));
    }

    public function create()
    {
        $brands = Brand::where('status', 'active')->orderBy('name')->get();
        $categories = Category::whereNull('fk_category_id')->where('status', 'active')->get();
        $units = Unit::where('status', 'active')->get();
        $suppliers = Supplier::where('status', 'active')->get();

        return view('products.create', compact('brands', 'categories', 'units', 'suppliers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'fk_brand_id' => 'nullable|exists:brands,id',
            'fk_category_id' => 'nullable|exists:categories,id',
            'fk_subcategory_id' => 'nullable|exists:categories,id',
            'fk_supplier_id' => 'nullable|exists:suppliers,id',
            'fk_unit_id' => 'nullable|exists:units,id',
            'sku' => 'nullable|string|max:50',
            'barcode' => 'nullable|string|max:50',
            'size' => 'nullable',
            'color' => 'nullable',
            'description' => 'nullable',
            'status' => 'required|in:active,inactive,archive',
        ]);

        Product::create([
            'name' => $validated['name'],
            'url_slug' => \Illuminate\Support\Str::slug($validated['name']),
            'fk_brand_id' => $validated['fk_brand_id'] ?? null,
            'fk_category_id' => $validated['fk_category_id'] ?? null,
            'fk_subcategory_id' => $validated['fk_subcategory_id'] ?? null,
            'fk_supplier_id' => $validated['fk_supplier_id'] ?? null,
            'fk_unit_id' => $validated['fk_unit_id'] ?? null,
            'sku' => $validated['sku'] ?? null,
            'barcode' => $validated['barcode'] ?? null,
            'size' => $validated['size'] ?? null,
            'color' => $validated['color'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'created_by' => auth('admin')->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Product created successfully.',
                'redirect' => route('products.index'),
            ]);
        }

        return redirect()->route('products.index')
            ->with('success', 'Product created successfully.');
    }

    public function edit(Product $product)
    {
        $brands = Brand::where('status', 'active')->orderBy('name')->get();
        $categories = Category::whereNull('fk_category_id')->where('status', 'active')->get();
        $units = Unit::where('status', 'active')->get();
        $suppliers = Supplier::where('status', 'active')->get();

        return view('products.edit', compact('product', 'brands', 'categories', 'units', 'suppliers'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'fk_brand_id' => 'nullable|exists:brands,id',
            'fk_category_id' => 'nullable|exists:categories,id',
            'fk_subcategory_id' => 'nullable|exists:categories,id',
            'fk_supplier_id' => 'nullable|exists:suppliers,id',
            'fk_unit_id' => 'nullable|exists:units,id',
            'sku' => 'nullable|string|max:50',
            'barcode' => 'nullable|string|max:50',
            'size' => 'nullable',
            'color' => 'nullable',
            'description' => 'nullable',
            'status' => 'required|in:active,inactive,archive',
        ]);

        $product->update([
            'name' => $validated['name'],
            'url_slug' => \Illuminate\Support\Str::slug($validated['name']),
            'fk_brand_id' => $validated['fk_brand_id'] ?? null,
            'fk_category_id' => $validated['fk_category_id'] ?? null,
            'fk_subcategory_id' => $validated['fk_subcategory_id'] ?? null,
            'fk_supplier_id' => $validated['fk_supplier_id'] ?? null,
            'fk_unit_id' => $validated['fk_unit_id'] ?? null,
            'sku' => $validated['sku'] ?? null,
            'barcode' => $validated['barcode'] ?? null,
            'size' => $validated['size'] ?? null,
            'color' => $validated['color'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'updated_by' => auth('admin')->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Product updated successfully.',
                'redirect' => route('products.index'),
            ]);
        }

        return redirect()->route('products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Request $request, Product $product)
    {
        $product->update(['deleted_by' => auth('admin')->id()]);
        $product->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Product deleted successfully.',
                'redirect' => route('products.index'),
            ]);
        }

        return redirect()->route('products.index')
            ->with('success', 'Product deleted successfully.');
    }
}
