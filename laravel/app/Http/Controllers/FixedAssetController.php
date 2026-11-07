<?php

namespace App\Http\Controllers;

use App\Accounting\AccountingPoster;
use App\Models\Configuration;
use App\Models\FixedAsset;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $adminId = auth('admin')->id();

        $fixedAsset = DB::transaction(function () use ($validated, $adminId) {
            $fixedAsset = FixedAsset::create([
                'name' => $validated['name'],
                'price' => $validated['price'],
                'description' => $validated['description'] ?? null,
                'accounting_version' => 1,
                'created_by' => $adminId,
            ]);

            app(AccountingPoster::class)->fixedAsset($fixedAsset, $adminId);

            return $fixedAsset;
        });

        ActivityLogger::created('Fixed Asset', $fixedAsset);

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

        $adminId = auth('admin')->id();
        $priceChanged = round((float) $fixedAsset->price, 2) !== round((float) $validated['price'], 2);

        DB::transaction(function () use ($fixedAsset, $validated, $adminId, $priceChanged) {
            $poster = app(AccountingPoster::class);

            if ($priceChanged || (! $fixedAsset->journal_entry_id && (float) $validated['price'] > 0)) {
                $poster->reverseOperational('fixed_asset', $fixedAsset->id, $adminId);

                $fixedAsset->update([
                    'name' => $validated['name'],
                    'price' => $validated['price'],
                    'description' => $validated['description'] ?? null,
                    'journal_entry_id' => null,
                    'accounting_version' => max(1, (int) $fixedAsset->accounting_version) + 1,
                    'updated_by' => $adminId,
                ]);

                $poster->fixedAsset($fixedAsset->fresh(), $adminId);

                return;
            }

            $fixedAsset->update([
                'name' => $validated['name'],
                'price' => $validated['price'],
                'description' => $validated['description'] ?? null,
                'updated_by' => $adminId,
            ]);
        });

        ActivityLogger::updated('Fixed Asset', $fixedAsset, $fixedAsset->toArray());

        return redirect()->route('fixed-assets.index')
            ->with('success', 'Fixed asset updated successfully.');
    }

    public function destroy(Request $request, FixedAsset $fixedAsset)
    {
        $adminId = auth('admin')->id();

        DB::transaction(function () use ($fixedAsset, $adminId) {
            app(AccountingPoster::class)->reverseOperational('fixed_asset', $fixedAsset->id, $adminId);

            $fixedAsset->update(['deleted_by' => $adminId, 'journal_entry_id' => null]);
            $fixedAsset->delete();
        });

        ActivityLogger::deleted('Fixed Asset', $fixedAsset);

        return redirect()->route('fixed-assets.index')
            ->with('success', 'Fixed asset deleted successfully.');
    }
}
