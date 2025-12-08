<?php

namespace App\Filament\Resources\TripResource\Pages;

use App\Filament\Resources\TripResource;
use App\Services\TravelDetail\PdsShareLinkService;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ViewTrip extends ViewRecord
{
    protected static string $resource = TripResource::class;

    public ?string $selectedCountry = null;
    public ?string $entryConditionsContent = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_share_link')
                ->label('Share-Link erstellen')
                ->icon('heroicon-o-share')
                ->color('primary')
                ->visible(fn () => config('travel_detail.pds.share_link_enabled'))
                ->requiresConfirmation()
                ->modalHeading('PDS Share-Link erstellen')
                ->modalDescription('Möchten Sie einen neuen Share-Link für diese Reise erstellen?')
                ->action(function () {
                    $service = app(PdsShareLinkService::class);
                    $link = $service->generateShareLink($this->record);

                    if ($link) {
                        Notification::make()
                            ->title('Share-Link erstellt')
                            ->body("TID: {$link->formatted_tid}")
                            ->success()
                            ->send();

                        $this->refreshFormData(['pds_share_url', 'pds_tid']);
                    } else {
                        Notification::make()
                            ->title('Fehler')
                            ->body('Share-Link konnte nicht erstellt werden')
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('view_share_link')
                ->label('Share-Link öffnen')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn () => $this->record->pds_share_url)
                ->openUrlInNewTab()
                ->visible(fn () => !empty($this->record->pds_share_url)),

            DeleteAction::make(),
        ];
    }

    /**
     * Entry conditions action - mountable via wire:click from country badges
     */
    public function showEntryConditionsAction(): Action
    {
        return Action::make('showEntryConditions')
            ->modalHeading('Einreisebestimmungen')
            ->modalWidth('5xl')
            ->modalContent(fn (array $arguments) => view('filament.resources.trip-resource.entry-conditions-modal', [
                'country' => $arguments['country'] ?? '',
                'countryName' => $this->getCountryName($arguments['country'] ?? ''),
                'content' => $this->fetchEntryConditions($arguments['country'] ?? ''),
                'travellers' => $this->record->travellers,
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Schließen');
    }

    /**
     * Get country name from ISO code
     */
    protected function getCountryName(string $code): string
    {
        $country = \App\Models\Country::where('iso_code', $code)->first();
        return $country ? $country->getName('de') : $code;
    }

    /**
     * Fetch entry conditions from PDS API
     */
    protected function fetchEntryConditions(string $countryCode): array
    {
        $travellers = $this->record->travellers;

        // Get unique nationalities from travellers
        $nationalities = $travellers->pluck('nationality')->unique()->filter()->values()->toArray();

        if (empty($nationalities)) {
            return [
                'error' => true,
                'message' => 'Keine Nationalitäten für Reisende hinterlegt',
                'results' => [],
            ];
        }

        $results = [];
        $apiUrl = config('services.passolution.api_url', env('PASSOLUTION_API_URL'));
        $apiKey = config('services.passolution.api_key', env('PDS_KEY'));

        foreach ($nationalities as $nationality) {
            try {
                $queryParams = [
                    'lang' => 'de',
                    'countries' => $countryCode,
                    'nat' => $nationality,
                ];

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Accept' => 'text/html, application/json',
                ])->timeout(30)->get($apiUrl . '/content/overview/html?' . http_build_query($queryParams));

                if ($response->successful()) {
                    $contentType = $response->header('Content-Type');
                    $body = $response->body();

                    $htmlContent = '';
                    if (strpos($contentType, 'application/json') !== false) {
                        $jsonData = $response->json();
                        if (isset($jsonData['records']) && is_array($jsonData['records'])) {
                            foreach ($jsonData['records'] as $record) {
                                if (isset($record['content'])) {
                                    $htmlContent .= $record['content'];
                                }
                            }
                        } elseif (isset($jsonData['content'])) {
                            $htmlContent = $jsonData['content'];
                        }
                    } else {
                        $htmlContent = $body;
                    }

                    // Get nationality name
                    $nationalityCountry = \App\Models\Country::where('iso_code', $nationality)->first();
                    $nationalityName = $nationalityCountry ? $nationalityCountry->getName('de') : $nationality;

                    // Get travellers with this nationality
                    $travellerNames = $travellers->where('nationality', $nationality)
                        ->map(fn ($t) => trim("{$t->first_name} {$t->last_name}"))
                        ->implode(', ');

                    $results[] = [
                        'nationality' => $nationality,
                        'nationalityName' => $nationalityName,
                        'travellerNames' => $travellerNames,
                        'content' => $htmlContent ?: '<p class="text-gray-500">Keine Informationen verfügbar</p>',
                        'success' => true,
                    ];
                } else {
                    Log::warning('PDS API error for entry conditions', [
                        'status' => $response->status(),
                        'country' => $countryCode,
                        'nationality' => $nationality,
                    ]);

                    $nationalityCountry = \App\Models\Country::where('iso_code', $nationality)->first();
                    $nationalityName = $nationalityCountry ? $nationalityCountry->getName('de') : $nationality;

                    $results[] = [
                        'nationality' => $nationality,
                        'nationalityName' => $nationalityName,
                        'travellerNames' => '',
                        'content' => '<p class="text-red-500">Fehler beim Abrufen der Daten</p>',
                        'success' => false,
                    ];
                }
            } catch (\Exception $e) {
                Log::error('Exception fetching entry conditions', [
                    'error' => $e->getMessage(),
                    'country' => $countryCode,
                    'nationality' => $nationality,
                ]);

                $nationalityCountry = \App\Models\Country::where('iso_code', $nationality)->first();
                $nationalityName = $nationalityCountry ? $nationalityCountry->getName('de') : $nationality;

                $results[] = [
                    'nationality' => $nationality,
                    'nationalityName' => $nationalityName,
                    'travellerNames' => '',
                    'content' => '<p class="text-red-500">Fehler: ' . htmlspecialchars($e->getMessage()) . '</p>',
                    'success' => false,
                ];
            }
        }

        return [
            'error' => false,
            'results' => $results,
        ];
    }
}
