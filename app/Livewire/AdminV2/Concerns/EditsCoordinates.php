<?php

namespace App\Livewire\AdminV2\Concerns;

use App\Support\AdminV2\Coordinates;
use Illuminate\Database\Eloquent\Model;

/**
 * Breiten- und Laengengrad eines Stammdaten-Eintrags, wahlweise aus Google
 * Maps uebernommen (Zahlenpaar oder Link einfuegen).
 */
trait EditsCoordinates
{
    public string $lat = '';

    public string $lng = '';

    public string $coordinatesImport = '';

    public function updatedCoordinatesImport(string $value): void
    {
        $this->resetErrorBag('coordinatesImport');

        if (trim($value) === '') {
            return;
        }

        $coordinates = Coordinates::parse($value);

        if (! $coordinates) {
            $this->addError('coordinatesImport', 'Darin stehen keine Koordinaten. Erwartet wird z. B. „48.1351, 11.5820“ oder ein Link aus Google Maps.');

            return;
        }

        $this->lat = Coordinates::format($coordinates['lat']);
        $this->lng = Coordinates::format($coordinates['lng']);
        $this->coordinatesImport = '';
        $this->resetErrorBag(['lat', 'lng']);
    }

    protected function fillCoordinates(Model $record): void
    {
        $this->lat = Coordinates::format($record->lat);
        $this->lng = Coordinates::format($record->lng);
    }

    /**
     * Vor dem Pruefen: Dezimalkomma annehmen.
     */
    protected function normalizeCoordinates(): void
    {
        $this->lat = str_replace(',', '.', trim($this->lat));
        $this->lng = str_replace(',', '.', trim($this->lng));
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function coordinateRules(): array
    {
        return [
            'lat' => ['nullable', 'required_with:lng', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'required_with:lat', 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function coordinateMessages(): array
    {
        return [
            'lat.required_with' => 'Zum Längengrad fehlt der Breitengrad.',
            'lng.required_with' => 'Zum Breitengrad fehlt der Längengrad.',
            'lat.numeric' => 'Der Breitengrad muss eine Zahl sein.',
            'lng.numeric' => 'Der Längengrad muss eine Zahl sein.',
            'lat.between' => 'Der Breitengrad liegt zwischen -90 und 90.',
            'lng.between' => 'Der Längengrad liegt zwischen -180 und 180.',
        ];
    }

    /**
     * @return array{lat: ?float, lng: ?float}
     */
    protected function coordinateValues(): array
    {
        return [
            'lat' => $this->lat === '' ? null : (float) $this->lat,
            'lng' => $this->lng === '' ? null : (float) $this->lng,
        ];
    }
}
