<?php

namespace App\Http\Controllers;

use App\Models\NotificationUnsubscribeToken;
use Illuminate\Http\Request;

class NotificationUnsubscribeController extends Controller
{
    public function show(string $token)
    {
        $unsubscribeToken = NotificationUnsubscribeToken::with('notificationRule')
            ->where('token', $token)
            ->firstOrFail();

        if ($unsubscribeToken->isUsed()) {
            return view('notifications.unsubscribed', [
                'alreadyUnsubscribed' => true,
            ]);
        }

        return view('notifications.unsubscribe', [
            'token' => $unsubscribeToken,
            'ruleName' => $unsubscribeToken->notificationRule?->name,
        ]);
    }

    public function unsubscribe(string $token)
    {
        $unsubscribeToken = NotificationUnsubscribeToken::with(['notificationRule', 'customer'])
            ->where('token', $token)
            ->firstOrFail();

        if ($unsubscribeToken->isUsed()) {
            return view('notifications.unsubscribed', [
                'alreadyUnsubscribed' => true,
            ]);
        }

        // Mark token as used
        $unsubscribeToken->update(['unsubscribed_at' => now()]);

        if ($unsubscribeToken->notification_rule_id) {
            // Deactivate just this specific rule
            $unsubscribeToken->notificationRule->update(['is_active' => false]);
        } else {
            // Disable all notifications for this customer
            $unsubscribeToken->customer->update(['notifications_enabled' => false]);
        }

        return view('notifications.unsubscribed', [
            'alreadyUnsubscribed' => false,
        ]);
    }
}
