<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityController extends Controller
{
    public function index()
    {
        return view('activities.index');
    }

    public function api(Request $request)
    {
        $query = Activity::with('admin')->latest();

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        if ($request->filled('admin_id')) {
            $query->byAdmin($request->admin_id);
        }

        if ($request->filled('from') && $request->filled('to')) {
            $query->byDateRange($request->from, $request->to);
        }

        $activities = $query->paginate($request->get('per_page', 25));

        return response()->json($activities);
    }

    public function stats()
    {
        $total = Activity::count();
        $today = Activity::whereDate('created_at', today())->count();
        $logins = Activity::where('name', 'User Login')->whereDate('created_at', today())->count();
        $errors = Activity::where('type', 'error')->whereDate('created_at', today())->count();

        $admins = Admin::select('id', 'first_name', 'last_name')
            ->whereIn('id', Activity::select('fk_admin_id')->distinct()->pluck('fk_admin_id'))
            ->get();

        $recentTypes = Activity::select('type', DB::raw('count(*) as count'))
            ->whereDate('created_at', today())
            ->groupBy('type')
            ->get();

        $activityByDay = Activity::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'total' => $total,
            'today' => $today,
            'logins' => $logins,
            'errors' => $errors,
            'admins' => $admins,
            'recent_types' => $recentTypes,
            'activity_by_day' => $activityByDay,
        ]);
    }

    public function clear(Request $request)
    {
        $query = Activity::query();

        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        if ($request->filled('before')) {
            $query->where('created_at', '<', $request->before);
        }

        $count = $query->count();
        $query->delete();

        return response()->json([
            'message' => "{$count} activities cleared.",
        ]);
    }
}
