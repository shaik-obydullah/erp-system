<?php

namespace App\Http\Controllers;

use App\Models\BillOfMaterial;
use App\Models\Configuration;
use App\Models\Production;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class ProductionController extends Controller
{
    public function index(Request $request)
    {
        $query = Production::with('billOfMaterial');

        $productions = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($productions);
        }

        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('productions.index', compact('productions', 'currencySymbol'));
    }

    public function create()
    {
        $boms = BillOfMaterial::orderBy('name')->get();

        return view('productions.create', compact('boms'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fk_bill_of_material_id' => 'required|exists:bill_of_materials,id',
            'production_cost' => 'required|numeric|min:0',
            'other_cost' => 'required|numeric|min:0',
            'expected_profit' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
        ]);

        Production::create([
            'fk_bill_of_material_id' => $validated['fk_bill_of_material_id'],
            'production_cost' => $validated['production_cost'],
            'other_cost' => $validated['other_cost'],
            'expected_profit' => $validated['expected_profit'],
            'quantity' => $validated['quantity'],
            'created_by' => auth('admin')->id(),
        ]);

        ActivityLogger::created('Production', Production::latest('id')->first());

        return redirect()->route('productions.index')
            ->with('success', 'Production entry created successfully.');
    }

    public function show(Production $production)
    {
        $production->load('billOfMaterial.product');
        return view('productions.show', compact('production'));
    }

    public function edit(Production $production)
    {
        $boms = BillOfMaterial::orderBy('name')->get();

        return view('productions.edit', compact('production', 'boms'));
    }

    public function update(Request $request, Production $production)
    {
        $validated = $request->validate([
            'fk_bill_of_material_id' => 'required|exists:bill_of_materials,id',
            'production_cost' => 'required|numeric|min:0',
            'other_cost' => 'required|numeric|min:0',
            'expected_profit' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
        ]);

        $production->update([
            'fk_bill_of_material_id' => $validated['fk_bill_of_material_id'],
            'production_cost' => $validated['production_cost'],
            'other_cost' => $validated['other_cost'],
            'expected_profit' => $validated['expected_profit'],
            'quantity' => $validated['quantity'],
            'updated_by' => auth('admin')->id(),
        ]);

        ActivityLogger::updated('Production', $production, $production->toArray());

        return redirect()->route('productions.index')
            ->with('success', 'Production entry updated successfully.');
    }

    public function destroy(Request $request, Production $production)
    {
        ActivityLogger::deleted('Production', $production);
        $production->update(['deleted_by' => auth('admin')->id()]);
        $production->delete();

        return redirect()->route('productions.index')
            ->with('success', 'Production entry deleted successfully.');
    }
}
