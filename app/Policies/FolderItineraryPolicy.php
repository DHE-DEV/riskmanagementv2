<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\Folder\FolderItinerary;

class FolderItineraryPolicy
{
    /**
     * Determine whether the customer can view any itineraries.
     */
    public function viewAny(Customer $customer): bool
    {
        return true;
    }

    /**
     * Determine whether the customer can view the itinerary.
     */
    public function view(Customer $customer, FolderItinerary $itinerary): bool
    {
        return $customer->id === $itinerary->customer_id;
    }

    /**
     * Determine whether the customer can create itineraries.
     */
    public function create(Customer $customer): bool
    {
        return true;
    }

    /**
     * Determine whether the customer can update the itinerary.
     */
    public function update(Customer $customer, FolderItinerary $itinerary): bool
    {
        return $customer->id === $itinerary->customer_id;
    }

    /**
     * Determine whether the customer can delete the itinerary.
     */
    public function delete(Customer $customer, FolderItinerary $itinerary): bool
    {
        // Only allow deleting pending itineraries
        return $customer->id === $itinerary->customer_id && $itinerary->status === 'pending';
    }

    /**
     * Determine whether the customer can restore the itinerary.
     */
    public function restore(Customer $customer, FolderItinerary $itinerary): bool
    {
        return $customer->id === $itinerary->customer_id;
    }

    /**
     * Determine whether the customer can permanently delete the itinerary.
     */
    public function forceDelete(Customer $customer, FolderItinerary $itinerary): bool
    {
        return $customer->id === $itinerary->customer_id;
    }
}
