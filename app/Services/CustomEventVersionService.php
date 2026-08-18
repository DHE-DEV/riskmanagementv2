<?php

namespace App\Services;

use App\Models\CustomEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Legt neue Versionen eines Ereignisses an.
 *
 * Bestehende Daten werden nie ueberschrieben: die neue Version ist eine
 * vollstaendige Kopie samt aller Zuordnungen (Laender inkl. Standort-Details,
 * Regionen, Staedte, Event-Typen, Labels, Organisationsknoten), startet aber
 * deaktiviert. Erst beim Aktivieren loest sie ihre Vorgaengerversion ab.
 */
class CustomEventVersionService
{
    /**
     * Neue, noch inaktive Version eines Ereignisses erzeugen.
     */
    public function createNewVersion(
        CustomEvent $source,
        ?int $userId = null,
        ?string $versionNote = null,
    ): CustomEvent {
        return DB::transaction(function () use ($source, $userId, $versionNote) {
            $source->loadMissing(['countries', 'regions', 'cities', 'eventTypes', 'labels', 'orgNodes']);

            // Gruppen-UUID nachziehen, falls das Ereignis noch aus der Zeit vor
            // der Versionierung stammt.
            if (! $source->version_group_uuid) {
                $source->forceFill(['version_group_uuid' => $source->uuid ?: (string) Str::uuid()])->saveQuietly();
            }

            $copy = $source->replicate([
                'clicks_count',
                'created_at',
                'updated_at',
                'deleted_at',
            ]);

            $copy->uuid = (string) Str::uuid();
            $copy->version_group_uuid = $source->version_group_uuid;
            $copy->version = $source->nextVersionNumber();
            $copy->version_parent_id = $source->getKey();
            $copy->version_note = $versionNote;
            $copy->superseded_by_id = null;
            $copy->superseded_at = null;
            $copy->activated_at = null;

            // Der Entwurf darf nicht neben dem Original erscheinen.
            $copy->is_active = false;
            $copy->archived = false;
            $copy->archived_at = null;

            $copy->created_by = $userId ?? $source->created_by;
            $copy->updated_by = $userId ?? $source->updated_by;

            $copy->save();

            $this->copyRelations($source, $copy);

            return $copy->fresh();
        });
    }

    /**
     * Alle Zuordnungen der Vorversion uebernehmen - inklusive Pivot-Daten,
     * damit Standortangaben und Koordinaten erhalten bleiben.
     */
    protected function copyRelations(CustomEvent $source, CustomEvent $copy): void
    {
        // Pro Land sind mehrere Standort-Datensaetze erlaubt, deshalb Zeile fuer
        // Zeile anhaengen statt sync() zu verwenden.
        foreach ($source->countries as $country) {
            $copy->countries()->attach($country->id, [
                'latitude' => $country->pivot->latitude,
                'longitude' => $country->pivot->longitude,
                'location_note' => $country->pivot->location_note,
                'use_default_coordinates' => $country->pivot->use_default_coordinates,
                'region_id' => $country->pivot->region_id,
                'city_id' => $country->pivot->city_id,
            ]);
        }

        foreach ($source->regions as $region) {
            $copy->regions()->attach($region->id, [
                'latitude' => $region->pivot->latitude,
                'longitude' => $region->pivot->longitude,
                'location_note' => $region->pivot->location_note,
                'use_default_coordinates' => $region->pivot->use_default_coordinates,
            ]);
        }

        foreach ($source->cities as $city) {
            $copy->cities()->attach($city->id, [
                'latitude' => $city->pivot->latitude,
                'longitude' => $city->pivot->longitude,
                'location_note' => $city->pivot->location_note,
                'use_default_coordinates' => $city->pivot->use_default_coordinates,
            ]);
        }

        if ($source->eventTypes->isNotEmpty()) {
            $copy->eventTypes()->sync($source->eventTypes->pluck('id')->all());
        }

        if ($source->labels->isNotEmpty()) {
            $copy->labels()->sync($source->labels->pluck('id')->all());
        }

        foreach ($source->orgNodes as $orgNode) {
            $copy->orgNodes()->attach($orgNode->id, [
                'start_date' => $orgNode->pivot->start_date,
                'end_date' => $orgNode->pivot->end_date,
            ]);
        }
    }

    /**
     * Feldweiser Vergleich zweier Versionen - Grundlage fuer die Anzeige
     * "was hat sich geaendert".
     *
     * @return array<int, array{field: string, label: string, old: ?string, new: ?string}>
     */
    public function diff(?CustomEvent $previous, CustomEvent $current): array
    {
        if (! $previous) {
            return [];
        }

        // Uebersetzte Felder ueber die Locale-Aufloesung vergleichen, alles
        // andere direkt aus der Spalte.
        $resolvers = [
            'title' => ['Titel', fn (CustomEvent $e) => $e->getTitle('de')],
            'popup_content' => ['Beschreibung', fn (CustomEvent $e) => strip_tags((string) $e->getPopupContent('de'))],
            'description' => ['Kurzbeschreibung', fn (CustomEvent $e) => $e->description],
            'priority' => ['Priorität', fn (CustomEvent $e) => CustomEvent::getPriorityOptions()[$e->priority] ?? $e->priority],
            'severity' => ['Schweregrad', fn (CustomEvent $e) => CustomEvent::getSeverityOptions()[$e->severity] ?? $e->severity],
            'start_date' => ['Startdatum', fn (CustomEvent $e) => $e->start_date],
            'end_date' => ['Enddatum', fn (CustomEvent $e) => $e->end_date],
            'is_nationwide' => ['Landesweit', fn (CustomEvent $e) => (bool) $e->is_nationwide],
            'source' => ['Quelle', fn (CustomEvent $e) => $e->source],
        ];

        $changes = [];

        foreach ($resolvers as $field => [$label, $resolver]) {
            $old = $this->stringify($resolver($previous));
            $new = $this->stringify($resolver($current));

            if ($old !== $new) {
                $changes[] = [
                    'field' => $field,
                    'label' => $label,
                    'old' => $old,
                    'new' => $new,
                ];
            }
        }

        // Laenderzuordnung mitvergleichen - sie ist fuer Kunden der haeufigste
        // inhaltliche Unterschied zwischen zwei Versionen.
        $oldCountries = $previous->loadMissing('countries')->countries
            ->map(fn ($c) => $c->getName('de'))->unique()->sort()->implode(', ');
        $newCountries = $current->loadMissing('countries')->countries
            ->map(fn ($c) => $c->getName('de'))->unique()->sort()->implode(', ');

        if ($oldCountries !== $newCountries) {
            $changes[] = [
                'field' => 'countries',
                'label' => 'Länder',
                'old' => $oldCountries,
                'new' => $newCountries,
            ];
        }

        return $changes;
    }

    protected function stringify(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value ? 'Ja' : 'Nein';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('d.m.Y H:i');
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return trim((string) $value);
    }
}
