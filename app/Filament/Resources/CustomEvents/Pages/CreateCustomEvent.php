<?php

namespace App\Filament\Resources\CustomEvents\Pages;

use App\Filament\Resources\CustomEvents\CustomEventResource;
use App\Models\Country;
use App\Models\EventType;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomEvent extends CreateRecord
{
    protected static string $resource = CustomEventResource::class;

    public ?string $infosystemSource = null;
    public ?string $infosystemSourceId = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set data source if coming from InfosystemEntry
        if (request()->has('source') && request()->get('source') === 'infosystem') {
            $data['data_source'] = 'passolution_infosystem';
            if (request()->has('source_id')) {
                $data['data_source_id'] = request()->get('source_id');
            }
        }

        return parent::mutateFormDataBeforeCreate($data);
    }

    protected function afterCreate(): void
    {
        // Mark InfosystemEntry as published if created from there
        if ($this->infosystemSourceId && $this->infosystemSource === 'infosystem') {
            $infosystemEntry = \App\Models\InfosystemEntry::where('api_id', $this->infosystemSourceId)->first();

            if ($infosystemEntry) {
                $infosystemEntry->update([
                    'is_published' => true,
                    'published_at' => now(),
                    'published_as_event_id' => $this->record->id,
                ]);
            }
        }

        // Update marker_icon from the first EventType
        $this->record->refresh();
        $this->record->load('eventTypes');

        if ($this->record->eventTypes->isNotEmpty()) {
            $firstEventType = $this->record->eventTypes->first();

            $this->record->updateQuietly([
                'marker_icon' => $firstEventType->icon,
                'event_type_id' => $firstEventType->id,
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }

    public function mount(): void
    {
        parent::mount();

        // Get URL parameters and fill form
        $request = request();

        // Store source info for later use in afterCreate
        if ($request->has('source') && $request->get('source') === 'infosystem') {
            $this->infosystemSource = $request->get('source');
            $this->infosystemSourceId = $request->get('source_id');
        }

        $data = [];

        // Map title from URL parameter
        if ($request->has('title') && $request->get('title')) {
            $data['title'] = $request->get('title');
        }

        // Map description to both description and popup_content fields
        if ($request->has('description') && $request->get('description')) {
            $data['description'] = $request->get('description');
            $data['popup_content'] = $request->get('description');
        }

        // Map country - prioritize country_code over country_name
        if ($request->has('country_code') && $request->get('country_code')) {
            $countryCode = strtoupper($request->get('country_code'));
            // Try to find country by ISO code (2 or 3 letter codes)
            $country = Country::where('iso_code', $countryCode)
                ->orWhere('iso3_code', $countryCode)
                ->first();
            if ($country) {
                $data['country_id'] = $country->id;
            }
        } elseif ($request->has('country_name') && $request->get('country_name')) {
            $countryName = $request->get('country_name');
            // Fallback: Try to find country by German name
            $country = Country::where('name_translations->de', $countryName)
                ->orWhere('german_name', $countryName)
                ->first();
            if ($country) {
                $data['country_id'] = $country->id;
            }
        }

        // Map InfosystemEntry tagtype to EventType
        if ($request->has('tagtype') && $request->get('tagtype')) {
            $eventTypeId = $this->mapTagtypeToEventType($request->get('tagtype'));
            if ($eventTypeId) {
                $data['event_type_id'] = $eventTypeId;
                // Also set for many-to-many relationship
                $data['eventTypes'] = [$eventTypeId];
            }
        }

        // Map event_date to start_date
        if ($request->has('event_date') && $request->get('event_date')) {
            $data['start_date'] = $request->get('event_date');
        }

        // Map start_date from URL parameter
        if ($request->has('start_date') && $request->get('start_date')) {
            $data['start_date'] = $request->get('start_date');
        }

        // Map severity to priority
        if ($request->has('severity') && $request->get('severity')) {
            $data['priority'] = $request->get('severity');
        }

        // Map is_active status
        if ($request->has('is_active')) {
            $data['is_active'] = (bool) $request->get('is_active');
        } elseif ($request->has('source') && $request->get('source') === 'infosystem') {
            // When creating from InfosystemEntry, always set is_active to true
            $data['is_active'] = true;
        }

        // Fill the form with the mapped data
        if (!empty($data)) {
            $this->form->fill($data);
        }
    }

    /**
     * Map InfosystemEntry tagtype to EventType
     * Mapping:
     * - tagtype 1: Umweltereignisse (environment)
     * - tagtype 2: Reiseverkehr (travel)
     * - tagtype 3: Sicherheit (safety)
     * - tagtype 4: Einreisebestimmungen (entry)
     * - tagtype 5: Allgemein (general)
     * - tagtype 6: Gesundheit (health)
     */
    protected function mapTagtypeToEventType(?string $tagtype): ?int
    {
        // Map InfosystemEntry tagtype to EventType code
        $mappings = [
            '1' => 'environment',  // Umweltereignisse
            '2' => 'travel',       // Reiseverkehr
            '3' => 'safety',       // Sicherheit
            '4' => 'entry',        // Einreisebestimmungen
            '5' => 'general',      // Allgemein
            '6' => 'health',       // Gesundheit
        ];

        $code = $mappings[$tagtype] ?? 'general';

        $eventType = EventType::where('code', $code)->first();

        return $eventType?->id;
    }
}
