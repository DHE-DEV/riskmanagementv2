<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Customer private channel for real-time folder updates
Broadcast::channel('customer.{customerId}', function ($user, $customerId) {
    // Check if user is authenticated via customer guard
    $customer = auth('customer')->user();
    if ($customer && (int) $customer->id === (int) $customerId) {
        return true;
    }
    return false;
});
