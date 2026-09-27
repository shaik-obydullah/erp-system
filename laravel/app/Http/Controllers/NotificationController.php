<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        return view('notifications.index');
    }

    public function api(Request $request)
    {
        $adminId = Auth::guard('admin')->id();
        $query = AdminNotification::forAdmin($adminId)->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('notification', 'like', "%{$search}%");
            });
        }

        if ($request->filled('view_status')) {
            $query->where('view_status', $request->view_status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $notifications = $query->paginate($request->get('per_page', 20));

        return response()->json($notifications);
    }

    public function unreadCount()
    {
        $adminId = Auth::guard('admin')->id();
        $count = AdminNotification::forAdmin($adminId)->unseen()->count();

        return response()->json(['count' => $count]);
    }

    public function unread(Request $request)
    {
        $adminId = Auth::guard('admin')->id();
        $notifications = AdminNotification::forAdmin($adminId)
            ->unseen()
            ->latest()
            ->limit(10)
            ->get();

        return response()->json($notifications);
    }

    public function markSeen($id)
    {
        $notification = AdminNotification::findOrFail($id);
        $notification->markAsSeen();

        return response()->json(['message' => 'Marked as seen.']);
    }

    public function markAllSeen()
    {
        $adminId = Auth::guard('admin')->id();
        AdminNotification::forAdmin($adminId)->unseen()->update(['view_status' => 'seen']);

        return response()->json(['message' => 'All notifications marked as seen.']);
    }

    public function destroy($id)
    {
        $notification = AdminNotification::findOrFail($id);
        $notification->delete();

        return response()->json(['message' => 'Notification deleted.']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:50',
            'notification' => 'required|string',
            'type' => 'in:info,success,warning,error',
            'link' => 'nullable|string',
            'module' => 'nullable|string|max:50',
            'fk_admin_id' => 'nullable|integer',
        ]);

        $notif = AdminNotification::create([
            'title' => $request->title,
            'notification' => $request->notification,
            'link' => $request->link,
            'type' => $request->get('type', 'info'),
            'module' => $request->module,
            'fk_admin_id' => $request->fk_admin_id,
            'view_status' => 'unseen',
            'created_by' => Auth::guard('admin')->id(),
        ]);

        return response()->json($notif, 201);
    }
}
