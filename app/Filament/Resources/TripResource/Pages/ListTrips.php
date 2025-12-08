<?php

namespace App\Filament\Resources\TripResource\Pages;

use App\Filament\Resources\TripResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Checkbox;
use Filament\Notifications\Notification;
use App\Services\TravelDetail\TripArchivalService;
use App\Services\TravelDetail\TripImportService;
use App\Services\TravelDetail\DirectShareLinkService;
use App\Services\TravelDetail\PdsShareLinkService;

class ListTrips extends ListRecords
{
    protected static string $resource = TripResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import_from_json')
                ->label('Neu aus JSON')
                ->icon('heroicon-o-document-plus')
                ->color('primary')
                ->modalHeading('Reise aus JSON erstellen')
                ->modalDescription('Fügen Sie das JSON-Payload ein, um eine neue Reise zu erstellen.')
                ->modalWidth('4xl')
                ->form([
                    Textarea::make('json_payload')
                        ->label('JSON Payload')
                        ->required()
                        ->rows(20)
                        ->maxLength(null)
                        ->placeholder('{"provider": {"id": "...", "sent_at": "..."}, "trip": {...}}')
                        ->helperText('Das JSON muss dem Travel Detail Schema entsprechen.'),
                ])
                ->modalSubmitActionLabel('Importieren')
                ->action(function (array $data) {
                    try {
                        $payload = json_decode($data['json_payload'], true);

                        if (json_last_error() !== JSON_ERROR_NONE) {
                            Notification::make()
                                ->title('Ungültiges JSON')
                                ->body('Das eingegebene JSON ist nicht gültig: ' . json_last_error_msg())
                                ->danger()
                                ->send();
                            return;
                        }

                        $service = app(TripImportService::class);
                        $trip = $service->importTrip($payload);

                        Notification::make()
                            ->title('Reise erfolgreich erstellt')
                            ->body("Trip ID: {$trip->id} - {$trip->external_trip_id}")
                            ->success()
                            ->send();

                        // Redirect to the new trip
                        return redirect()->to(TripResource::getUrl('view', ['record' => $trip]));

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Fehler beim Import')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('share_link_from_json')
                ->label('Share-Link aus JSON')
                ->icon('heroicon-o-link')
                ->color('success')
                ->modalHeading('Share-Link aus JSON erstellen')
                ->modalDescription('Erstellen Sie einen PDS Share-Link aus JSON. Unterstützt beide Formate: einfaches Schema oder Travel Detail Schema mit Itinerary.')
                ->modalWidth('4xl')
                ->form([
                    Textarea::make('json_payload')
                        ->label('JSON Payload')
                        ->required()
                        ->rows(20)
                        ->maxLength(null)
                        ->placeholder('{
  "trip": {
    "itinerary": [...],
    "travellers": [...]
  }
}

oder

{
  "trip": {
    "start_date": "2025-01-15",
    "end_date": "2025-01-22"
  },
  "destinations": [{"code": "ES"}],
  "nationalities": ["DE"]
}')
                        ->helperText('Unterstützt: (1) Travel Detail Schema mit itinerary + travellers, oder (2) einfaches Schema mit destinations + nationalities.'),
                    Checkbox::make('save_to_database')
                        ->label('Reise in Datenbank speichern')
                        ->helperText('Nur bei Travel Detail Schema mit itinerary möglich. Bei einfachem Schema wird nur der Share-Link erstellt.')
                        ->default(false),
                ])
                ->modalSubmitActionLabel('Share-Link erstellen')
                ->action(function (array $data) {
                    try {
                        $payload = json_decode($data['json_payload'], true);

                        if (json_last_error() !== JSON_ERROR_NONE) {
                            Notification::make()
                                ->title('Ungültiges JSON')
                                ->body('Das eingegebene JSON ist nicht gültig: ' . json_last_error_msg())
                                ->danger()
                                ->send();
                            return;
                        }

                        $saveToDatabase = $data['save_to_database'] ?? false;
                        $hasItinerary = isset($payload['trip']['itinerary']) && is_array($payload['trip']['itinerary']);

                        // If save to database is requested
                        if ($saveToDatabase) {
                            if (!$hasItinerary) {
                                Notification::make()
                                    ->title('Hinweis')
                                    ->body('Das einfache Schema unterstützt keine Datenbankspeicherung. Es wird nur der Share-Link erstellt.')
                                    ->warning()
                                    ->send();
                            } else {
                                // Create trip in database first
                                $importService = app(TripImportService::class);
                                $trip = $importService->importTrip($payload);

                                // Generate share link from the created trip
                                $shareLinkService = app(PdsShareLinkService::class);
                                $shareLink = $shareLinkService->generateShareLink($trip);

                                if ($shareLink) {
                                    Notification::make()
                                        ->title('Reise und Share-Link erstellt')
                                        ->body("Trip ID: {$trip->id} | TID: {$shareLink->formatted_tid}")
                                        ->success()
                                        ->actions([
                                            \Filament\Notifications\Actions\Action::make('view_trip')
                                                ->label('Reise öffnen')
                                                ->url(TripResource::getUrl('view', ['record' => $trip])),
                                            \Filament\Notifications\Actions\Action::make('open_link')
                                                ->label('Share-Link öffnen')
                                                ->url($shareLink->share_url)
                                                ->openUrlInNewTab(),
                                        ])
                                        ->persistent()
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->title('Reise erstellt, Share-Link fehlgeschlagen')
                                        ->body("Trip ID: {$trip->id} - Share-Link konnte nicht erstellt werden.")
                                        ->warning()
                                        ->actions([
                                            \Filament\Notifications\Actions\Action::make('view_trip')
                                                ->label('Reise öffnen')
                                                ->url(TripResource::getUrl('view', ['record' => $trip])),
                                        ])
                                        ->persistent()
                                        ->send();
                                }

                                return;
                            }
                        }

                        // Just create share link without database storage
                        $service = app(DirectShareLinkService::class);
                        $result = $service->generateFromPayload($payload);

                        if (!$result['success']) {
                            Notification::make()
                                ->title('Fehler')
                                ->body($result['error'] ?? 'Share-Link konnte nicht erstellt werden')
                                ->danger()
                                ->send();
                            return;
                        }

                        Notification::make()
                            ->title('Share-Link erstellt')
                            ->body("TID: {$result['formatted_tid']}")
                            ->success()
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('open')
                                    ->label('Link öffnen')
                                    ->url($result['share_url'])
                                    ->openUrlInNewTab(),
                            ])
                            ->persistent()
                            ->send();

                    } catch (\Illuminate\Validation\ValidationException $e) {
                        $errors = collect($e->errors())->flatten()->implode(', ');
                        Notification::make()
                            ->title('Validierungsfehler')
                            ->body($errors)
                            ->danger()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Fehler')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn () => config('travel_detail.pds.share_link_enabled')),

            Action::make('archive_stats')
                ->label('Archivierung')
                ->icon('heroicon-o-archive-box')
                ->color('gray')
                ->modalHeading('Archivierungs-Statistik')
                ->modalContent(function () {
                    $service = app(TripArchivalService::class);
                    $stats = $service->getStatistics();

                    return view('filament.pages.archive-stats', ['stats' => $stats]);
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Schließen'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\TravelDetailStatsOverview::class,
        ];
    }
}
