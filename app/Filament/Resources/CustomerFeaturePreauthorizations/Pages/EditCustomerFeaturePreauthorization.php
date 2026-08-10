<?php

namespace App\Filament\Resources\CustomerFeaturePreauthorizations\Pages;

use App\Filament\Resources\CustomerFeaturePreauthorizations\CustomerFeaturePreauthorizationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomerFeaturePreauthorization extends EditRecord
{
    protected static string $resource = CustomerFeaturePreauthorizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                // Klarstellen, dass Loeschen nichts zurueckdreht.
                ->modalDescription('Loescht nur die Vormerkung. Eine bereits erteilte Freischaltung bleibt beim Kunden bestehen.'),
        ];
    }
}
