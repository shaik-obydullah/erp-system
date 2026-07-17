<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index()
    {
        $admins = Admin::with('roles')->get();

        return view('admins.index', compact('admins'));
    }

    public function create()
    {
        $roles = Role::orderBy('name')->get();

        return view('admins.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => 'required|email|max:50|unique:admins,email',
            'password' => 'required|string|min:8|confirmed',
            'sex' => 'required|in:male,female',
            'mobile' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $admin = Admin::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'] ?? null,
            'email' => $validated['email'],
            'password' => $validated['password'],
            'sex' => $validated['sex'],
            'mobile' => $validated['mobile'] ?? null,
            'address' => $validated['address'] ?? null,
            'status' => $validated['status'],
            'created_by' => auth('admin')->id(),
        ]);

        $admin->roles()->sync($validated['roles']);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Admin created successfully.',
                'redirect' => route('admins.index'),
            ]);
        }

        return redirect()->route('admins.index')
            ->with('success', 'Admin created successfully.');
    }

    public function edit(Admin $admin)
    {
        $admin->load('roles');
        $roles = Role::orderBy('name')->get();
        $adminRoleIds = $admin->roles->pluck('id')->toArray();

        return view('admins.edit', compact('admin', 'roles', 'adminRoleIds'));
    }

    public function update(Request $request, Admin $admin)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => 'required|email|max:50|unique:admins,email,'.$admin->id,
            'password' => 'nullable|string|min:8|confirmed',
            'sex' => 'required|in:male,female',
            'mobile' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'status' => 'required|in:active,inactive',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $data = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'] ?? null,
            'email' => $validated['email'],
            'sex' => $validated['sex'],
            'mobile' => $validated['mobile'] ?? null,
            'address' => $validated['address'] ?? null,
            'status' => $validated['status'],
            'updated_by' => auth('admin')->id(),
        ];

        if (! empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        $admin->update($data);
        $admin->roles()->sync($validated['roles']);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Admin updated successfully.',
                'redirect' => route('admins.index'),
            ]);
        }

        return redirect()->route('admins.index')
            ->with('success', 'Admin updated successfully.');
    }

    public function destroy(Request $request, Admin $admin)
    {
        if ($admin->id === auth('admin')->id()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'You cannot delete your own account.'], 422);
            }

            return back()->with('error', 'You cannot delete your own account.');
        }

        $admin->roles()->detach();
        $admin->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Admin deleted successfully.',
                'redirect' => route('admins.index'),
            ]);
        }

        return redirect()->route('admins.index')
            ->with('success', 'Admin deleted successfully.');
    }
}
