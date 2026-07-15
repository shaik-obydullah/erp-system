<?php

namespace App\Services;

use App\Models\Admin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class ActivityService
{
    public static function log(string $type, string $name, ?int $adminId = null): void
    {
        $adminId ??= Auth::guard('admin')->id();

        DB::table('activities')->insert([
            'fk_admin_id' => $adminId,
            'type' => $type,
            'name' => $name,
            'ip_address' => Request::ip(),
            'visitor_country' => null,
            'visitor_state' => null,
            'visitor_city' => null,
            'visitor_address' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'updated_by' => $adminId,
        ]);
    }

    public static function success(string $name, ?int $adminId = null): void
    {
        self::log('success', $name, $adminId);
    }

    public static function warning(string $name, ?int $adminId = null): void
    {
        self::log('warning', $name, $adminId);
    }

    public static function error(string $name, ?int $adminId = null): void
    {
        self::log('error', $name, $adminId);
    }

    public static function login(?Admin $admin = null): void
    {
        $admin ??= Auth::guard('admin')->user();
        self::success('Login successful', $admin?->id);
    }

    public static function logout(?Admin $admin = null): void
    {
        $admin ??= Auth::guard('admin')->user();
        self::success('Logout successful', $admin?->id);
    }
}
