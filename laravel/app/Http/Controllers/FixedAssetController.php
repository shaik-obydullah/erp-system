<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\FixedAsset;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class FixedAssetController extends Controller
{
    public function index(Request $request)
    {
        $query = FixedAsset::query();

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $fixedAssets = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($fixedAssets);
        }

        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('fixed-assets.index', compact('fixedAssets', 'currencySymbol'));
    }

    public function create()
    {
        return view('fixed-assets.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        FixedAsset::create([
            'name' => $validated['name'],
            'price' => $validated['price'],
            'description' => $validated['description'] ?? null,
            'created_by' => auth('admin')->id(),
        ]);

        ActivityLogger::created('Fixed Asset', FixedAsset::latest('id')->first());

        return redirect()->route('fixed-assets.index')
            ->with('success', 'Fixed asset created successfully.');
    }

    public function edit(FixedAsset $fixedAsset)
    {
        return view('fixed-assets.edit', compact('fixedAsset'));
    }

    public function update(Request $request, FixedAsset $fixedAsset)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $fixedAsset->update([
            'name' => $validated['name'],
            'price' => $validated['price'],
            'description' => $validated['description'] ?? null,
            'updated_by' => auth('admin')->id(),
        ]);

        ActivityLogger::updated('Fixed Asset', $fixedAsset, $fixedAsset->toArray());

        return redirect()->route('fixed-assets.index')
            ->with('success', 'Fixed asset updated successfully.');
    }

    public function destroy(Request $request, FixedAsset $fixedAsset)
    {
        ActivityLogger::deleted('Fixed Asset', $fixedAsset);
        $fixedAsset->update(['deleted_by' => auth('admin')->id()]);
        $fixedAsset->delete();

        return redirect()->route('fixed-assets.index')
            ->with('success', 'Fixed asset deleted successfully.');
    }
}
