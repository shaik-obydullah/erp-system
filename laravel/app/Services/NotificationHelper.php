<?php

namespace App\Services;

use App\Models\AdminNotification;
use Illuminate\Support\Facades\Auth;

class NotificationHelper
{
    public static function create(
        string $title,
        string $message,
        string $type = 'info',
        ?string $link = null,
        ?string $module = null,
        ?int $adminId = null
    ): AdminNotification {
        return AdminNotification::create([
            'title' => $title,
            'notification' => $message,
            'type' => $type,
            'link' => $link,
            'module' => $module,
            'fk_admin_id' => $adminId,
            'view_status' => 'unseen',
            'created_by' => Auth::guard('admin')->id(),
        ]);
    }

    public static function stockAlert(int $adminId, string $productName, int $quantity, string $warehouse): AdminNotification
    {
        return self::create(
            'Low Stock Alert',
            "{$productName} is low on stock ({$quantity} remaining) in {$warehouse}.",
            'warning',
            '/stocks',
            'stocks',
            $adminId
        );
    }

    public static function orderPlaced(int $adminId, int $orderId, string $customer): AdminNotification
    {
        return self::create(
            'New Order',
            "Order #{$orderId} placed by {$customer}.",
            'success',
            "/orders/{$orderId}",
            'orders',
            $adminId
        );
    }

    public static function paymentReceived(int $adminId, float $amount, string $reference): AdminNotification
    {
        return self::create(
            'Payment Received',
            "Payment of $" . number_format($amount, 2) . " received ({$reference}).",
            'success',
            '/transactions',
            'transactions',
            $adminId
        );
    }

    public static function systemError(string $description): void
    {
        self::create(
            'System Error',
            $description,
            'error',
            null,
            'system'
        );
    }
}
