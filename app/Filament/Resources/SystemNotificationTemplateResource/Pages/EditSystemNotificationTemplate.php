<?php

namespace App\Filament\Resources\SystemNotificationTemplateResource\Pages;

use App\Filament\Resources\SystemNotificationTemplateResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditSystemNotificationTemplate extends EditRecord
{
    protected static string $resource = SystemNotificationTemplateResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Zurück zur Liste')
                ->icon('heroicon-o-arrow-left')
                ->url($this->getResource()::getUrl('index')),
        ];
    }
}
