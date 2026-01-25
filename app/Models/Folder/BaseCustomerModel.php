<?php

namespace App\Models\Folder;

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
            if (auth('customer')->check() && ! $model->customer_id) {
                $model->customer_id = auth('customer')->id();
            }
        });

        // Global Scope: Only show records belonging to authenticated customer
        static::addGlobalScope('customer', function (Builder $builder) {
            if (auth('customer')->check()) {
                $builder->where($builder->getModel()->getTable().'.customer_id', auth('customer')->id());
            }
        });
    }

    /**
     * Scope to bypass customer scope (for admin use)
     */
    public function scopeWithoutCustomerScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('customer');
    }
}
