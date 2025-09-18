<?php

namespace App\Filament\Resources\CustomEvents\Pages;

use App\Filament\Resources\CustomEvents\CustomEventResource;
use App\Models\Country;
use App\Models\EventType;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomEvent extends CreateRecord
{
    protected static string $resource = CustomEventResource::class;

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
        if (request()->has('source_id') && request()->get('source') === 'infosystem') {
            $infosystemEntry = \App\Models\InfosystemEntry::where('api_id', request()->get('source_id'))->first();
            if ($infosystemEntry) {
                $infosystemEntry->update([
                    'is_published' => true,
                    'published_at' => now(),
                    'published_as_event_id' => $this->record->id,
                ]);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function mount(): void
    {
        parent::mount();

        // Get URL parameters and fill form
        $request = request();
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
        }

        // Fill the form with the mapped data
        if (!empty($data)) {
            $this->form->fill($data);
        }
    }

    /**
     * Map InfosystemEntry tagtype to EventType
     * Based on actual data analysis:
     * - tagtype 3: Often visa/entry related topics
     * - tagtype 4: Often travel/strike related topics
     */
    protected function mapTagtypeToEventType(?string $tagtype): ?int
    {
        // Map InfosystemEntry tagtype to EventType code
        $mappings = [
            '1' => 'entry',       // entry -> Einreisebestimmungen
            '2' => 'safety',      // safety -> Sicherheit
            '3' => 'entry',       // visa/entry topics -> Einreisebestimmungen
            '4' => 'travel',      // travel/strikes -> Reiseverkehr
            '5' => 'health',      // health -> Gesundheit
            '6' => 'other',       // other -> Sonstiges
        ];

        $code = $mappings[$tagtype] ?? 'other';

        $eventType = EventType::where('code', $code)->first();

        return $eventType?->id;
    }
}
