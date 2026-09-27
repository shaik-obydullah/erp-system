<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function index(Request $request)
    {
        $query = Currency::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $currencies = $query->orderBy('code')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($currencies);
        }

        return view('currencies.index', compact('currencies'));
    }

    public function create()
    {
        return view('currencies.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:currencies,code',
            'name' => 'required|string|max:50',
            'symbol' => 'required|string|max:10',
            'exchange_rate' => 'required|numeric|min:0',
            'is_base' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $hasCurrencies = Currency::withTrashed()->count() > 0;

        $data = [
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'symbol' => $validated['symbol'],
            'exchange_rate' => $validated['exchange_rate'],
            'is_base' => $validated['is_base'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => auth('admin')->id(),
        ];

        if (! $hasCurrencies) {
            $data['is_base'] = true;
            $data['exchange_rate'] = 1.000000;
        }

        if ($data['is_base']) {
            Currency::where('is_base', true)->update(['is_base' => false]);
        }

        Currency::create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Currency created successfully.',
                'redirect' => route('currencies.index'),
            ]);
        }

        return redirect()->route('currencies.index')
            ->with('success', 'Currency created successfully.');
    }

    public function edit(Currency $currency)
    {
        return view('currencies.edit', compact('currency'));
    }

    public function update(Request $request, Currency $currency)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:currencies,code,'.$currency->id,
            'name' => 'required|string|max:50',
            'symbol' => 'required|string|max:10',
            'exchange_rate' => 'required|numeric|min:0',
            'is_base' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $data = [
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'symbol' => $validated['symbol'],
            'exchange_rate' => $validated['exchange_rate'],
            'is_base' => $validated['is_base'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
            'updated_by' => auth('admin')->id(),
        ];

        if ($data['is_base']) {
            Currency::where('is_base', true)
                ->where('id', '!=', $currency->id)
                ->update(['is_base' => false]);
            $data['exchange_rate'] = 1.000000;
        }

        $currency->update($data);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Currency updated successfully.',
                'redirect' => route('currencies.index'),
            ]);
        }

        return redirect()->route('currencies.index')
            ->with('success', 'Currency updated successfully.');
    }

    public function destroy(Request $request, Currency $currency)
    {
        if ($currency->is_base) {
            $message = 'Cannot delete the base currency.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }
            return redirect()->route('currencies.index')->with('error', $message);
        }

        $currency->update(['deleted_by' => auth('admin')->id()]);
        $currency->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Currency deleted successfully.',
                'redirect' => route('currencies.index'),
            ]);
        }

        return redirect()->route('currencies.index')
            ->with('success', 'Currency deleted successfully.');
    }

    public function setBase(Request $request, Currency $currency)
    {
        Currency::where('is_base', true)->update(['is_base' => false]);

        $currency->update([
            'is_base' => true,
            'exchange_rate' => 1.000000,
            'updated_by' => auth('admin')->id(),
        ]);

        return redirect()->route('currencies.index')
            ->with('success', "Base currency set to {$currency->code}.");
    }
}
