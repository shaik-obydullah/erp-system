<?php

namespace App\Http\Controllers;

use App\Models\BillOfMaterial;
use App\Models\Configuration;
use App\Models\ProductionPlanning;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class ProductionPlanningController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductionPlanning::with('billOfMaterial');

        if ($search = $request->input('search')) {
            $query->whereHas('billOfMaterial', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if (in_array($request->input('finalized'), ['yes', 'no'], true)) {
            $query->where('finalized', $request->input('finalized'));
        }

        $plannings = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($plannings);
        }

        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('production-plannings.index', compact('plannings', 'currencySymbol'));
    }

    public function create()
    {
        $boms = BillOfMaterial::orderBy('name')->get();

        return view('production-plannings.create', compact('boms'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fk_bill_of_material_id' => 'required|exists:bill_of_materials,id',
            'production_cost' => 'required|numeric|min:0',
            'other_cost' => 'required|numeric|min:0',
            'expected_profit' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
            'finalized' => 'required|in:yes,no',
        ]);

        $planning = ProductionPlanning::create([
            'fk_bill_of_material_id' => $validated['fk_bill_of_material_id'],
            'production_cost' => $validated['production_cost'],
            'other_cost' => $validated['other_cost'],
            'expected_profit' => $validated['expected_profit'],
            'quantity' => $validated['quantity'],
            'finalized' => $validated['finalized'],
            'created_by' => auth('admin')->id(),
        ]);

        ActivityLogger::created('Production Planning', $planning);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Production planning created successfully.',
                'redirect' => route('production-plannings.index'),
            ]);
        }

        return redirect()->route('production-plannings.index')
            ->with('success', 'Production planning created successfully.');
    }

    public function edit(ProductionPlanning $productionPlanning)
    {
        $boms = BillOfMaterial::orderBy('name')->get();

        return view('production-plannings.edit', compact('productionPlanning', 'boms'));
    }

    public function update(Request $request, ProductionPlanning $productionPlanning)
    {
        $validated = $request->validate([
            'fk_bill_of_material_id' => 'required|exists:bill_of_materials,id',
            'production_cost' => 'required|numeric|min:0',
            'other_cost' => 'required|numeric|min:0',
            'expected_profit' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
            'finalized' => 'required|in:yes,no',
        ]);

        $productionPlanning->update([
            'fk_bill_of_material_id' => $validated['fk_bill_of_material_id'],
            'production_cost' => $validated['production_cost'],
            'other_cost' => $validated['other_cost'],
            'expected_profit' => $validated['expected_profit'],
            'quantity' => $validated['quantity'],
            'finalized' => $validated['finalized'],
            'updated_by' => auth('admin')->id(),
        ]);

        ActivityLogger::updated('Production Planning', $productionPlanning, $productionPlanning->toArray());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Production planning updated successfully.',
                'redirect' => route('production-plannings.index'),
            ]);
        }

        return redirect()->route('production-plannings.index')
            ->with('success', 'Production planning updated successfully.');
    }
}
