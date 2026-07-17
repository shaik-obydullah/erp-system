<?php

namespace App\Services;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public static function log(
        string $name,
        string $type = 'success',
        ?string $description = null,
        $subject = null,
        ?array $oldData = null,
        ?array $newData = null
    ): Activity {
        $request = request();

        return Activity::create([
            'fk_admin_id' => Auth::guard('admin')->id(),
            'type' => $type,
            'name' => $name,
            'description' => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'ip_address' => $request->ip() ?? '0.0.0.0',
            'visitor_country' => null,
            'visitor_state' => null,
            'visitor_city' => null,
            'visitor_address' => null,
            'old_data' => $oldData,
            'new_data' => $newData,
            'updated_by' => Auth::guard('admin')->id(),
        ]);
    }

    public static function login(string $email): Activity
    {
        return self::log('User Login', 'success', "Admin logged in: {$email}");
    }

    public static function logout(): Activity
    {
        return self::log('User Logout', 'success', 'Admin logged out');
    }

    public static function failedLogin(string $email, string $reason): Activity
    {
        return self::log('Failed Login', 'error', "Login failed for {$email}: {$reason}");
    }

    public static function created(string $moduleName, $model): Activity
    {
        return self::log(
            "Created {$moduleName}",
            'success',
            "{$moduleName} created: #{$model->id}",
            $model,
            null,
            $model->toArray()
        );
    }

    public static function updated(string $moduleName, $model, array $changes): Activity
    {
        $old = array_intersect_key($model->getOriginal(), $changes);
        $new = array_intersect_key($model->getAttributes(), $changes);

        return self::log(
            "Updated {$moduleName}",
            'success',
            "{$moduleName} updated: #{$model->id}",
            $model,
            $old,
            $new
        );
    }

    public static function deleted(string $moduleName, $model): Activity
    {
        return self::log(
            "Deleted {$moduleName}",
            'warning',
            "{$moduleName} deleted: #{$model->id}",
            $model,
            $model->toArray(),
            null
        );
    }

    public static function error(string $name, string $description): Activity
    {
        return self::log($name, 'error', $description);
    }
}
