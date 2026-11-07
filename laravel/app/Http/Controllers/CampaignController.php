<?php

namespace App\Http\Controllers;

use App\Accounting\AccountingPoster;
use App\Models\Campaign;
use App\Models\Configuration;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'spent_amount' => 'nullable|numeric|min:0',
        ]);

        $adminId = auth('admin')->id();

        $campaign = DB::transaction(function () use ($validated, $adminId) {
            $campaign = Campaign::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'status' => $validated['status'],
                'budget' => $validated['budget'],
                'spent_amount' => $validated['spent_amount'] ?? 0,
                'accounting_version' => 1,
                'created_by' => $adminId,
            ]);

            app(AccountingPoster::class)->campaignSpend($campaign, $adminId);

            return $campaign;
        });

        ActivityLogger::created('Campaign', $campaign);

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
            'spent_amount' => 'nullable|numeric|min:0',
        ]);

        $adminId = auth('admin')->id();
        $spent = $validated['spent_amount'] ?? 0;
        $spendChanged = round((float) $campaign->spent_amount, 2) !== round((float) $spent, 2);

        DB::transaction(function () use ($campaign, $validated, $spent, $adminId, $spendChanged) {
            $poster = app(AccountingPoster::class);

            if ($spendChanged || (! $campaign->journal_entry_id && $spent > 0)) {
                $poster->reverseOperational('campaign', $campaign->id, $adminId);

                $campaign->update([
                    'name' => $validated['name'],
                    'description' => $validated['description'] ?? null,
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                    'status' => $validated['status'],
                    'budget' => $validated['budget'],
                    'spent_amount' => $spent,
                    'journal_entry_id' => null,
                    'accounting_version' => max(1, (int) $campaign->accounting_version) + 1,
                    'updated_by' => $adminId,
                ]);

                $poster->campaignSpend($campaign->fresh(), $adminId);

                return;
            }

            $campaign->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'status' => $validated['status'],
                'budget' => $validated['budget'],
                'spent_amount' => $spent,
                'updated_by' => $adminId,
            ]);
        });

        ActivityLogger::updated('Campaign', $campaign, $campaign->toArray());

        return redirect()->route('campaigns.index')
            ->with('success', 'Campaign updated successfully.');
    }

    public function destroy(Request $request, Campaign $campaign)
    {
        $adminId = auth('admin')->id();

        DB::transaction(function () use ($campaign, $adminId) {
            app(AccountingPoster::class)->reverseOperational('campaign', $campaign->id, $adminId);

            $campaign->update(['deleted_by' => $adminId, 'journal_entry_id' => null]);
            $campaign->delete();
        });

        ActivityLogger::deleted('Campaign', $campaign);

        return redirect()->route('campaigns.index')
            ->with('success', 'Campaign deleted successfully.');
    }
}
