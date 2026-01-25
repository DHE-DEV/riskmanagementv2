<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Customer private channel for real-time folder updates
Broadcast::channel('customer.{customerId}', function ($customer, $customerId) {
    return (int) $customer->id === (int) $customerId;
});
