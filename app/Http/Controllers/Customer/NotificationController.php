<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $customer = auth('customer')->user();
        $notifications = $customer->notifications()
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $customer->unreadNotifications()->count()
        ]);
    }

    public function markAsRead($id)
    {
        $customer = auth('customer')->user();
        $notification = $customer->notifications()->find($id);

        if ($notification) {
            $notification->markAsRead();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 404);
    }

    public function markAllAsRead()
    {
        $customer = auth('customer')->user();
        $customer->unreadNotifications->markAsRead();

        return response()->json(['success' => true]);
    }

    public function delete($id)
    {
        $customer = auth('customer')->user();
        $notification = $customer->notifications()->find($id);

        if ($notification) {
            $notification->delete();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 404);
    }
}
