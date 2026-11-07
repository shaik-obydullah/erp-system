<?php

namespace App\Http\Controllers;

use App\Accounting\AccountingPoster;
use App\Models\BillOfMaterial;
use App\Models\Configuration;
use App\Models\Production;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $adminId = auth('admin')->id();

        $production = DB::transaction(function () use ($validated, $adminId) {
            $production = Production::create([
                'fk_bill_of_material_id' => $validated['fk_bill_of_material_id'],
                'production_cost' => $validated['production_cost'],
                'other_cost' => $validated['other_cost'],
                'expected_profit' => $validated['expected_profit'],
                'quantity' => $validated['quantity'],
                'accounting_version' => 1,
                'created_by' => $adminId,
            ]);

            app(AccountingPoster::class)->production($production, $adminId);

            return $production;
        });

        ActivityLogger::created('Production', $production);

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

        $adminId = auth('admin')->id();
        $oldTotal = round((float) $production->production_cost + (float) $production->other_cost, 2);
        $newTotal = round((float) $validated['production_cost'] + (float) $validated['other_cost'], 2);

        DB::transaction(function () use ($production, $validated, $adminId, $oldTotal, $newTotal) {
            $poster = app(AccountingPoster::class);

            if ($oldTotal !== $newTotal || (! $production->journal_entry_id && $newTotal > 0)) {
                $poster->reverseOperational('production', $production->id, $adminId);

                $production->update([
                    'fk_bill_of_material_id' => $validated['fk_bill_of_material_id'],
                    'production_cost' => $validated['production_cost'],
                    'other_cost' => $validated['other_cost'],
                    'expected_profit' => $validated['expected_profit'],
                    'quantity' => $validated['quantity'],
                    'journal_entry_id' => null,
                    'accounting_version' => max(1, (int) $production->accounting_version) + 1,
                    'updated_by' => $adminId,
                ]);

                $poster->production($production->fresh(), $adminId);

                return;
            }

            $production->update([
                'fk_bill_of_material_id' => $validated['fk_bill_of_material_id'],
                'production_cost' => $validated['production_cost'],
                'other_cost' => $validated['other_cost'],
                'expected_profit' => $validated['expected_profit'],
                'quantity' => $validated['quantity'],
                'updated_by' => $adminId,
            ]);
        });

        ActivityLogger::updated('Production', $production, $production->toArray());

        return redirect()->route('productions.index')
            ->with('success', 'Production entry updated successfully.');
    }

    public function destroy(Request $request, Production $production)
    {
        $adminId = auth('admin')->id();

        DB::transaction(function () use ($production, $adminId) {
            app(AccountingPoster::class)->reverseOperational('production', $production->id, $adminId);

            $production->update(['deleted_by' => $adminId, 'journal_entry_id' => null]);
            $production->delete();
        });

        ActivityLogger::deleted('Production', $production);

        return redirect()->route('productions.index')
            ->with('success', 'Production entry deleted successfully.');
    }
}
