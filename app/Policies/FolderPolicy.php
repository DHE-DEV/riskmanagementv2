<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\Folder\Folder;

class FolderPolicy
{
    /**
     * Determine whether the customer can view any folders.
     */
    public function viewAny(Customer $customer): bool
    {
        return true;
    }

    /**
     * Determine whether the customer can view the folder.
     */
    public function view(Customer $customer, Folder $folder): bool
    {
        return $customer->id === $folder->customer_id;
    }

    /**
     * Determine whether the customer can create folders.
     */
    public function create(Customer $customer): bool
    {
        return true;
    }

    /**
     * Determine whether the customer can update the folder.
     */
    public function update(Customer $customer, Folder $folder): bool
    {
        return $customer->id === $folder->customer_id;
    }

    /**
     * Determine whether the customer can delete the folder.
     */
    public function delete(Customer $customer, Folder $folder): bool
    {
        // Only allow deleting draft folders
        return $customer->id === $folder->customer_id && $folder->status === 'draft';
    }

    /**
     * Determine whether the customer can restore the folder.
     */
    public function restore(Customer $customer, Folder $folder): bool
    {
        return $customer->id === $folder->customer_id;
    }

    /**
     * Determine whether the customer can permanently delete the folder.
     */
    public function forceDelete(Customer $customer, Folder $folder): bool
    {
        return $customer->id === $folder->customer_id;
    }
}
