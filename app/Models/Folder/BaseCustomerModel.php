<?php

namespace App\Models\Folder;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

abstract class BaseCustomerModel extends Model
{
    use HasUuids;

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // Automatically set customer_id on creation
        static::creating(function ($model) {
            if (! $model->customer_id && $customerId = static::currentCustomerId()) {
                $model->customer_id = $customerId;
            }
        });

        // Global Scope: Only show records belonging to authenticated customer
        static::addGlobalScope('customer', function (Builder $builder) {
            if ($customerId = static::currentCustomerId()) {
                $builder->where($builder->getModel()->getTable().'.customer_id', $customerId);
            }
        });
    }

    /**
     * Resolve the customer the current request acts on behalf of.
     *
     * The customer guard uses the session driver, so it never applies to API
     * requests. Those authenticate through a Sanctum token instead, and without
     * this fallback the global scope below would silently be skipped - exposing
     * every customer's records to any valid token.
     *
     * Returns null for admins and for unauthenticated contexts such as queued
     * jobs and console commands, which keeps their unscoped access intact.
     */
    protected static function currentCustomerId(): int|string|null
    {
        if (auth('customer')->check()) {
            return auth('customer')->id();
        }

        $tokenOwner = auth('sanctum')->user();

        return $tokenOwner instanceof Customer ? $tokenOwner->getKey() : null;
    }

    /**
     * Scope to bypass customer scope (for admin use)
     */
    public function scopeWithoutCustomerScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('customer');
    }
}
