<?php

namespace App\Filament\Resources\CustomEvents\Concerns;

use App\Models\CustomEvent;
use App\Services\CustomEventVersionService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

/**
 * Aktionen der Versionsverwaltung fuer die Detail- und Bearbeiten-Seite.
 */
trait HasVersionActions
{
    /**
     * "Duplizieren": legt eine neue, noch inaktive Version an und springt
     * direkt in deren Bearbeitung.
     */
    protected function createVersionAction(): Action
    {
        return Action::make('create_version')
            ->label('Duplizieren (neue Version)')
            ->icon('heroicon-o-document-duplicate')
            ->color('primary')
            ->modalHeading('Neue Version anlegen')
            ->modalDescription('Es wird eine vollstaendige Kopie dieses Ereignisses als naechste Version angelegt. Die Kopie ist zunaechst inaktiv – erst beim Aktivieren loest sie die aktuelle Version ab.')
            ->modalSubmitActionLabel('Neue Version anlegen')
            ->schema([
                Textarea::make('version_note')
                    ->label('Aenderungsnotiz')
                    ->rows(3)
                    ->maxLength(2000)
                    ->helperText('Kurz beschreiben, was sich gegenueber der Vorversion aendert. Wird in der Versionshistorie angezeigt.'),
            ])
            ->action(function (array $data) {
                /** @var CustomEvent $record */
                $record = $this->getRecord();

                $newVersion = app(CustomEventVersionService::class)->createNewVersion(
                    $record,
                    auth()->id(),
                    $data['version_note'] ?? null,
                );

                Notification::make()
                    ->title("Version {$newVersion->version} angelegt")
                    ->body('Die Kopie ist noch nicht aktiv. Nach der Bearbeitung ueber "Version aktivieren" freischalten.')
                    ->success()
                    ->send();

                $this->redirect(static::getResource()::getUrl('edit', ['record' => $newVersion]));
            });
    }

    /**
     * Schaltet diese Version live und deaktiviert dabei die Vorgaengerversion.
     */
    protected function activateVersionAction(): Action
    {
        return Action::make('activate_version')
            ->label('Version aktivieren')
            ->icon('heroicon-o-rocket-launch')
            ->color('success')
            ->visible(fn (): bool => ! $this->getRecord()->is_active)
            ->requiresConfirmation()
            ->modalHeading('Version aktivieren')
            ->modalDescription(function (): string {
                /** @var CustomEvent $record */
                $record = $this->getRecord();

                $previous = $record->versionHistory()
                    ->firstWhere(fn (CustomEvent $version) => $version->is_active && $version->id !== $record->id);

                return $previous
                    ? "Diese Version wird veroeffentlicht. Version {$previous->version} wird dabei automatisch deaktiviert und bleibt als Historie erhalten."
                    : 'Diese Version wird veroeffentlicht und ab sofort ausgeliefert.';
            })
            ->modalSubmitActionLabel('Ja, aktivieren')
            ->action(function () {
                /** @var CustomEvent $record */
                $record = $this->getRecord();

                $record->activateVersion(auth()->id());

                Notification::make()
                    ->title("Version {$record->version} ist aktiv")
                    ->body('Vorherige Versionen wurden deaktiviert. Die Benachrichtigungslaeufe beruecksichtigen ab sofort diese Version.')
                    ->success()
                    ->send();

                $this->redirect(static::getResource()::getUrl('view', ['record' => $record]));
            });
    }
}
