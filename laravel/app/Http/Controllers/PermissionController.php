<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::orderBy('group')->get()->groupBy('group');
        $rolePermissions = Permission::with('roles')->get()->groupBy('group');

        return view('permissions.index', compact('permissions', 'rolePermissions'));
    }
}
