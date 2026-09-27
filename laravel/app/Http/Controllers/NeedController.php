<?php

namespace App\Http\Controllers;

use App\Models\Need;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class NeedController extends Controller
{
    public function index(Request $request)
    {
        $query = Need::query();

        if ($search = $request->input('search')) {
            $query->where('description', 'like', "%{$search}%");
        }

        $needs = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($needs);
        }

        return view('needs.index', compact('needs'));
    }

    public function create()
    {
        return view('needs.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:500',
            'date_identified' => 'required|date',
        ]);

        Need::create([
            'description' => $validated['description'],
            'date_identified' => $validated['date_identified'],
            'created_by' => auth('admin')->id(),
        ]);

        ActivityLogger::created('Procurement Need', Need::latest('id')->first());

        return redirect()->route('needs.index')
            ->with('success', 'Procurement need created successfully.');
    }

    public function edit(Need $need)
    {
        return view('needs.edit', compact('need'));
    }

    public function update(Request $request, Need $need)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:500',
            'date_identified' => 'required|date',
        ]);

        $need->update([
            'description' => $validated['description'],
            'date_identified' => $validated['date_identified'],
            'updated_by' => auth('admin')->id(),
        ]);

        ActivityLogger::updated('Procurement Need', $need, $need->toArray());

        return redirect()->route('needs.index')
            ->with('success', 'Procurement need updated successfully.');
    }

    public function destroy(Request $request, Need $need)
    {
        ActivityLogger::deleted('Procurement Need', $need);
        $need->update(['deleted_by' => auth('admin')->id()]);
        $need->delete();

        return redirect()->route('needs.index')
            ->with('success', 'Procurement need deleted successfully.');
    }
}
