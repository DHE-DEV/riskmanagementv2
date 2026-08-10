<?php

namespace App\Filament\Resources\CustomerFeaturePreauthorizations\Pages;

use App\Filament\Resources\CustomerFeaturePreauthorizations\CustomerFeaturePreauthorizationResource;
use App\Models\CustomerFeaturePreauthorization;
use App\Services\CustomerFeaturePreauthorizationService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerFeaturePreauthorization extends CreateRecord
{
    protected static string $resource = CustomerFeaturePreauthorizationResource::class;

    /**
     * Gibt es zu der Account-ID schon Konten, soll die Vormerkung nicht bis zum
     * naechsten Login warten.
     */
    protected function afterCreate(): void
    {
        /** @var CustomerFeaturePreauthorization $record */
        $record = $this->record;

        $result = app(CustomerFeaturePreauthorizationService::class)
            ->applyToExistingCustomers($record->feature_key, [$record->pds_account_id]);

        if ($result['customers'] > 0) {
            Notification::make()
                ->title('Sofort angewendet')
                ->body("{$result['customers']} bestehende Konten angepasst.")
                ->success()
                ->send();

            return;
        }

        Notification::make()
            ->title('Vorgemerkt')
            ->body('Greift beim ersten Login dieses Accounts.')
            ->info()
            ->send();
    }
}
