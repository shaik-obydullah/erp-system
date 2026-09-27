<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Configuration;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $query = Campaign::query();

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $campaigns = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json($campaigns);
        }

        $currencySymbol = Configuration::get('currency_symbol', '$');

        return view('campaigns.index', compact('campaigns', 'currencySymbol'));
    }

    public function create()
    {
        return view('campaigns.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:planned,active,completed,canceled',
            'budget' => 'required|numeric|min:0',
        ]);

        Campaign::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => $validated['status'],
            'budget' => $validated['budget'],
            'created_by' => auth('admin')->id(),
        ]);

        ActivityLogger::created('Campaign', Campaign::latest('id')->first());

        return redirect()->route('campaigns.index')
            ->with('success', 'Campaign created successfully.');
    }

    public function edit(Campaign $campaign)
    {
        return view('campaigns.edit', compact('campaign'));
    }

    public function update(Request $request, Campaign $campaign)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:planned,active,completed,canceled',
            'budget' => 'required|numeric|min:0',
        ]);

        $campaign->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => $validated['status'],
            'budget' => $validated['budget'],
            'updated_by' => auth('admin')->id(),
        ]);

        ActivityLogger::updated('Campaign', $campaign, $campaign->toArray());

        return redirect()->route('campaigns.index')
            ->with('success', 'Campaign updated successfully.');
    }

    public function destroy(Request $request, Campaign $campaign)
    {
        ActivityLogger::deleted('Campaign', $campaign);
        $campaign->update(['deleted_by' => auth('admin')->id()]);
        $campaign->delete();

        return redirect()->route('campaigns.index')
            ->with('success', 'Campaign deleted successfully.');
    }
}
