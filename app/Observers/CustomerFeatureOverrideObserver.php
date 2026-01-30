<?php

namespace App\Observers;

use App\Models\CustomerFeatureOverride;
use App\Services\CustomerFeatureService;

class CustomerFeatureOverrideObserver
{
    protected CustomerFeatureService $featureService;

    public function __construct(CustomerFeatureService $featureService)
    {
        $this->featureService = $featureService;
    }

    /**
     * Handle the CustomerFeatureOverride "created" event.
     */
    public function created(CustomerFeatureOverride $customerFeatureOverride): void
    {
        $this->featureService->clearCache($customerFeatureOverride->customer_id);
    }

    /**
     * Handle the CustomerFeatureOverride "updated" event.
     */
    public function updated(CustomerFeatureOverride $customerFeatureOverride): void
    {
        $this->featureService->clearCache($customerFeatureOverride->customer_id);
    }

    /**
     * Handle the CustomerFeatureOverride "deleted" event.
     */
    public function deleted(CustomerFeatureOverride $customerFeatureOverride): void
    {
        $this->featureService->clearCache($customerFeatureOverride->customer_id);
    }

    /**
     * Handle the CustomerFeatureOverride "saved" event.
     */
    public function saved(CustomerFeatureOverride $customerFeatureOverride): void
    {
        $this->featureService->clearCache($customerFeatureOverride->customer_id);
    }
}
