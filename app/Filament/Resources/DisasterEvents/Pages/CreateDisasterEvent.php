<?php

namespace App\Filament\Resources\DisasterEvents\Pages;

use App\Filament\Resources\DisasterEvents\DisasterEventResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDisasterEvent extends CreateRecord
{
    protected static string $resource = DisasterEventResource::class;
}
