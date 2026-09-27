<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Configuration;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
        $categories = Category::whereNull('fk_category_id')->where('status', 'active')
            ->with(['children' => fn($q) => $q->where('status', 'active')->orderBy('name')])
            ->orderBy('name')->get();
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
            'images.*' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $images = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $images[] = $this->processImage($file);
            }
        }

        Product::create([
            'name' => $validated['name'],
            'url_slug' => Str::slug($validated['name']),
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
            'image' => !empty($images) ? json_encode($images) : null,
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
        $categories = Category::whereNull('fk_category_id')->where('status', 'active')
            ->with(['children' => fn($q) => $q->where('status', 'active')->orderBy('name')])
            ->orderBy('name')->get();
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
            'images.*' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $existingImages = $product->parsed_images;

        if ($request->input('remove_images')) {
            $removeIndexes = explode(',', $request->input('remove_images'));
            foreach ($removeIndexes as $index) {
                $index = (int) trim($index);
                if (isset($existingImages[$index])) {
                    $path = public_path('uploads/products/' . $existingImages[$index]);
                    if (file_exists($path)) {
                        unlink($path);
                    }
                    unset($existingImages[$index]);
                }
            }
            $existingImages = array_values($existingImages);
        }

        $newImages = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $newImages[] = $this->processImage($file);
            }
        }

        $allImages = array_merge($existingImages, $newImages);

        $product->update([
            'name' => $validated['name'],
            'url_slug' => Str::slug($validated['name']),
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
            'image' => !empty($allImages) ? json_encode($allImages) : null,
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
        if ($product->image) {
            $images = json_decode($product->image, true) ?? [];
            foreach ($images as $img) {
                $path = public_path('uploads/products/' . $img);
                if (file_exists($path)) {
                    unlink($path);
                }
            }
        }

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

    public function barcodes(Request $request)
    {
        $query = Product::where('status', 'active')->whereNotNull('barcode');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('name')->paginate(50)->withQueryString();

        return view('products.barcodes', compact('products'));
    }

    public function export()
    {
        $products = Product::with(['brand', 'category', 'supplier'])->orderBy('name')->get();

        $filename = 'products_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($products) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Name', 'SKU', 'Barcode', 'Brand', 'Category', 'Supplier', 'Size', 'Color', 'Description', 'Status']);

            foreach ($products as $product) {
                fputcsv($file, [
                    $product->name,
                    $product->sku ?? '',
                    $product->barcode ?? '',
                    $product->brand->name ?? '',
                    $product->category->name ?? '',
                    $product->supplier->name ?? '',
                    $product->size ?? '',
                    $product->color ?? '',
                    $product->description ?? '',
                    $product->status,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = fopen($request->file('csv_file')->getPathname(), 'r');
        $header = fgetcsv($file);

        $imported = 0;
        $skipped = 0;
        $errors = [];

        while (($row = fgetcsv($file)) !== false) {
            if (count($row) < 2) {
                $skipped++;
                continue;
            }

            $data = array_combine(array_slice($header, 0, count($row)), $row);

            $name = trim($data['Name'] ?? '');

            if (empty($name)) {
                $skipped++;
                continue;
            }

            try {
                $brandId = null;
                $categoryId = null;
                $supplierId = null;

                $brandName = trim($data['Brand'] ?? '');
                if (!empty($brandName)) {
                    $brand = Brand::where('name', $brandName)->first();
                    if ($brand) $brandId = $brand->id;
                }

                $categoryName = trim($data['Category'] ?? '');
                if (!empty($categoryName)) {
                    $category = Category::where('name', $categoryName)->first();
                    if ($category) $categoryId = $category->id;
                }

                $supplierName = trim($data['Supplier'] ?? '');
                if (!empty($supplierName)) {
                    $supplier = Supplier::where('name', $supplierName)->first();
                    if ($supplier) $supplierId = $supplier->id;
                }

                Product::create([
                    'name' => $name,
                    'url_slug' => Str::slug($name),
                    'sku' => trim($data['SKU'] ?? '') ?: null,
                    'barcode' => trim($data['Barcode'] ?? '') ?: null,
                    'fk_brand_id' => $brandId,
                    'fk_category_id' => $categoryId,
                    'fk_supplier_id' => $supplierId,
                    'size' => trim($data['Size'] ?? '') ?: null,
                    'color' => trim($data['Color'] ?? '') ?: null,
                    'description' => trim($data['Description'] ?? '') ?: null,
                    'status' => in_array(strtolower(trim($data['Status'] ?? '')), ['inactive', 'archive']) ? strtolower(trim($data['Status'])) : 'active',
                    'created_by' => auth('admin')->id(),
                ]);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "{$name}: " . $e->getMessage();
                $skipped++;
            }
        }

        fclose($file);

        $message = "{$imported} products imported.";
        if ($skipped > 0) $message .= " {$skipped} skipped.";
        if (!empty($errors)) $message .= " Errors: " . implode('; ', array_slice($errors, 0, 5));

        return redirect()->route('products.index')
            ->with($imported > 0 ? 'success' : 'error', $message);
    }

    public function uploadMedia(Request $request)
    {
        $request->validate([
            'file' => 'required|file|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $dir = public_path('uploads/products');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $file = $request->file('file');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->move($dir, $filename);

        return response()->json([
            'url' => asset('uploads/products/' . $filename),
            'filename' => $filename,
        ]);
    }

    private function processImage($file, int $maxWidth = 800, int $quality = 85): string
    {
        $dir = public_path('uploads/products');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $original = imagecreatefromstring(file_get_contents($file->getRealPath()));
        if (!$original) {
            throw new \RuntimeException('Failed to process image.');
        }

        $origW = imagesx($original);
        $origH = imagesy($original);

        if ($origW <= $maxWidth) {
            $newW = $origW;
            $newH = $origH;
        } else {
            $newW = $maxWidth;
            $newH = (int) round($origH * ($maxWidth / $origW));
        }

        $resized = imagecreatetruecolor($newW, $newH);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $original, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $dir . '/' . $filename;

        $ext = strtolower($file->getClientOriginalExtension());
        if ($ext === 'png') {
            imagepng($resized, $path);
        } elseif ($ext === 'gif') {
            imagegif($resized, $path);
        } elseif ($ext === 'webp') {
            imagewebp($resized, $path, $quality);
        } else {
            imagejpeg($resized, $path, $quality);
        }

        imagedestroy($original);
        imagedestroy($resized);

        return $filename;
    }
}
