<?php

namespace App\Http\Controllers;

use App\Models\BillOfMaterial;
use App\Models\Product;
use App\Models\Unit;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class BillOfMaterialController extends Controller
{
    public function index(Request $request)
    {
        $query = BillOfMaterial::with('product');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $boms = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($boms);
        }

        return view('bill-of-materials.index', compact('boms'));
    }

    public function create()
    {
        $products = Product::where('status', 'active')->orderBy('name')->get();
        $units = Unit::orderBy('name')->get();

        return view('bill-of-materials.create', compact('products', 'units'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fk_product_id' => 'required|exists:products,id',
            'name' => 'required|string|max:200',
            'unit' => 'nullable|integer|min:0',
            'quantity' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
        ]);

        BillOfMaterial::create([
            'fk_product_id' => $validated['fk_product_id'],
            'name' => $validated['name'],
            'unit' => $validated['unit'] ?? null,
            'quantity' => $validated['quantity'] ?? null,
            'description' => $validated['description'] ?? null,
            'created_by' => auth('admin')->id(),
        ]);

        ActivityLogger::created('Bill of Material', BillOfMaterial::latest('id')->first());

        return redirect()->route('bill-of-materials.index')
            ->with('success', 'Bill of materials created successfully.');
    }

    public function edit(BillOfMaterial $billOfMaterial)
    {
        $products = Product::where('status', 'active')->orderBy('name')->get();
        $units = Unit::orderBy('name')->get();

        return view('bill-of-materials.edit', compact('billOfMaterial', 'products', 'units'));
    }

    public function update(Request $request, BillOfMaterial $billOfMaterial)
    {
        $validated = $request->validate([
            'fk_product_id' => 'required|exists:products,id',
            'name' => 'required|string|max:200',
            'unit' => 'nullable|integer|min:0',
            'quantity' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $billOfMaterial->update([
            'fk_product_id' => $validated['fk_product_id'],
            'name' => $validated['name'],
            'unit' => $validated['unit'] ?? null,
            'quantity' => $validated['quantity'] ?? null,
            'description' => $validated['description'] ?? null,
            'updated_by' => auth('admin')->id(),
        ]);

        ActivityLogger::updated('Bill of Material', $billOfMaterial, $billOfMaterial->toArray());

        return redirect()->route('bill-of-materials.index')
            ->with('success', 'Bill of materials updated successfully.');
    }

    public function destroy(Request $request, BillOfMaterial $billOfMaterial)
    {
        ActivityLogger::deleted('Bill of Material', $billOfMaterial);
        $billOfMaterial->update(['deleted_by' => auth('admin')->id()]);
        $billOfMaterial->delete();

        return redirect()->route('bill-of-materials.index')
            ->with('success', 'Bill of materials deleted successfully.');
    }
}
