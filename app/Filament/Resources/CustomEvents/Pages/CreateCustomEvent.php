<?php

namespace App\Filament\Resources\CustomEvents\Pages;

use App\Filament\Resources\CustomEvents\CustomEventResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomEvent extends CreateRecord
{
    protected static string $resource = CustomEventResource::class;
}
