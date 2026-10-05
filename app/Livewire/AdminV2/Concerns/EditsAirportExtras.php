<?php

namespace App\Livewire\AdminV2\Concerns;

use App\Support\AdminV2\AirportExtras;
use Illuminate\Database\Eloquent\Model;

/**
 * Lounges, Mobilitaetsangebote und Hotels – fuer Flughaefen und
 * Flughafen-Codes gleich.
 */
trait EditsAirportExtras
{
    /** @var array<int, array<string, mixed>> */
    public array $lounges = [];

    /** @var array<string, array<string, mixed>> */
    public array $mobility = [];

    /** @var array<int, array<string, mixed>> */
    public array $hotels = [];

    protected function fillAirportExtras(?Model $record): void
    {
        $this->lounges = AirportExtras::loungesToForm($record?->lounges);
        $this->mobility = AirportExtras::mobilityToForm($record?->mobility_options);
        $this->hotels = AirportExtras::hotelsToForm($record?->nearby_hotels);
    }

    /**
     * @return array<string, mixed>
     */
    protected function airportExtrasValues(): array
    {
        return [
            'lounges' => AirportExtras::loungesFromForm($this->lounges),
            'mobility_options' => AirportExtras::mobilityFromForm($this->mobility),
            'nearby_hotels' => AirportExtras::hotelsFromForm($this->hotels),
        ];
    }

    /**
     * Welche der drei Bereiche sich gegenueber dem gespeicherten Stand nicht
     * geaendert haben – verglichen in der vereinheitlichten Form, die auch das
     * Speichern schreibt. Vor dem Speichern aufrufen.
     *
     * @return array<int, string>
     */
    protected function unchangedAirportExtras(?Model $record): array
    {
        if (! $record?->exists) {
            return [];
        }

        $before = [
            'lounges' => AirportExtras::loungesFromForm(AirportExtras::loungesToForm($record->lounges)),
            'mobility_options' => AirportExtras::mobilityFromForm(AirportExtras::mobilityToForm($record->mobility_options)),
            'nearby_hotels' => AirportExtras::hotelsFromForm(AirportExtras::hotelsToForm($record->nearby_hotels)),
        ];
        $after = $this->airportExtrasValues();

        return array_keys(array_filter($before, fn (array $value, string $key) => $value == $after[$key], ARRAY_FILTER_USE_BOTH));
    }

    /**
     * Lounges, Mobilitaet und Hotels in lesbarer Form fuer die KI-Pruefungen.
     *
     * @return array{lounges: array<int, string>, mobility: array<int, string>, hotels: array<int, string>}
     */
    protected function airportExtrasContext(): array
    {
        return AirportExtras::describe($this->lounges, $this->mobility, $this->hotels);
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function airportExtrasRules(): array
    {
        return [
            'lounges.*.name' => ['nullable', 'string', 'max:255'],
            'lounges.*.url' => ['nullable', 'string', 'max:2000'],
            'lounges.*.price_per_person' => ['nullable', 'string', 'max:20'],
            'hotels.*.name' => ['nullable', 'string', 'max:255'],
            'hotels.*.distance_km' => ['nullable', 'string', 'max:20'],
            'hotels.*.booking_url' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function addLounge(): void
    {
        $this->lounges[] = AirportExtras::emptyLounge();
    }

    public function removeLounge(int $index): void
    {
        unset($this->lounges[$index]);
        $this->lounges = array_values($this->lounges);
    }

    public function addHotel(): void
    {
        $this->hotels[] = AirportExtras::emptyHotel();
    }

    public function removeHotel(int $index): void
    {
        unset($this->hotels[$index]);
        $this->hotels = array_values($this->hotels);
    }

    /**
     * Eine Zeile zu einem Mobilitaetsangebot mit Liste (Mietwagen-Anbieter, ÖPNV, Parken).
     */
    public function addMobilityRow(string $option): void
    {
        $list = AirportExtras::mobility()[$option]['list'] ?? null;

        if (! $list) {
            return;
        }

        $this->mobility[$option][$list['key']][] = array_fill_keys(array_keys($list['fields']), '');
    }

    public function removeMobilityRow(string $option, int $index): void
    {
        $list = AirportExtras::mobility()[$option]['list'] ?? null;

        if (! $list) {
            return;
        }

        unset($this->mobility[$option][$list['key']][$index]);
        $this->mobility[$option][$list['key']] = array_values($this->mobility[$option][$list['key']]);
    }
}
