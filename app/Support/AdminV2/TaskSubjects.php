<?php

namespace App\Support\AdminV2;

use App\Models\Airline;
use App\Models\Airport;
use App\Models\AirportCode;
use App\Models\ApiClient;
use App\Models\City;
use App\Models\Continent;
use App\Models\Country;
use App\Models\CustomEvent;
use App\Models\Customer;
use App\Models\CustomerFeaturePreauthorization;
use App\Models\PluginClient;
use App\Models\Region;
use App\Models\TravelAlertOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Datensaetze, an die sich eine Aufgabe haengen laesst: je Art das Modell,
 * die Bezeichnung, die Seite zum Bearbeiten und woran man den Eintrag erkennt.
 *
 * Die Art ("airline", "customer", …) steht in der Adresse einer neuen Aufgabe
 * (?subject=airline&subject_id=44); gespeichert wird der Bezug als
 * subject_type/subject_id an der Aufgabe.
 */
class TaskSubjects
{
    /**
     * "category" nennt die Rubrik, die bei einer neuen Aufgabe vorbelegt ist.
     *
     * @return array<string, array{model: class-string<Model>, category?: string, label: string, route: string, title: callable(Model): ?string}>
     */
    public static function all(): array
    {
        $name = fn (Model $record) => $record->getName('de');

        return [
            'event' => ['model' => CustomEvent::class, 'category' => 'Global Travel Monitor', 'label' => 'Ereignis', 'route' => 'adminv2.events.edit', 'title' => fn (CustomEvent $event) => $event->getTitle('de')],
            'continent' => ['model' => Continent::class, 'category' => 'Stammdaten', 'label' => 'Kontinent', 'route' => 'adminv2.master-data.continents.edit', 'title' => $name],
            'country' => ['model' => Country::class, 'category' => 'Stammdaten', 'label' => 'Land', 'route' => 'adminv2.master-data.countries.edit', 'title' => $name],
            'region' => ['model' => Region::class, 'category' => 'Stammdaten', 'label' => 'Region', 'route' => 'adminv2.master-data.regions.edit', 'title' => $name],
            'city' => ['model' => City::class, 'category' => 'Stammdaten', 'label' => 'Stadt', 'route' => 'adminv2.master-data.cities.edit', 'title' => $name],
            'airport' => ['model' => Airport::class, 'category' => 'Stammdaten', 'label' => 'Flughafen', 'route' => 'adminv2.master-data.airports.edit', 'title' => fn (Airport $airport) => trim($airport->name.($airport->iata_code ? ' ('.$airport->iata_code.')' : ''))],
            'airport-code' => ['model' => AirportCode::class, 'category' => 'Stammdaten', 'label' => 'Flughafen-Code', 'route' => 'adminv2.master-data.airport-codes.edit', 'title' => fn (AirportCode $code) => $code->name],
            'airline' => ['model' => Airline::class, 'category' => 'Stammdaten', 'label' => 'Airline', 'route' => 'adminv2.master-data.airlines.edit', 'title' => fn (Airline $airline) => trim($airline->name.($airline->iata_code ? ' ('.$airline->iata_code.')' : ''))],
            'customer' => ['model' => Customer::class, 'label' => 'Kunde', 'route' => 'adminv2.customer-management.customers.edit', 'title' => fn (Customer $customer) => $customer->company_name ?: ($customer->name ?: $customer->email)],
            'feature-preauthorization' => ['model' => CustomerFeaturePreauthorization::class, 'label' => 'Feature-Vormerkung', 'route' => 'adminv2.customer-management.feature-preauthorizations.edit', 'title' => fn (CustomerFeaturePreauthorization $preauthorization) => 'Account '.$preauthorization->pds_account_id],
            'travel-alert-order' => ['model' => TravelAlertOrder::class, 'label' => 'TravelAlert-Bestellung', 'route' => 'adminv2.customer-management.travel-alert-orders.show', 'title' => fn (TravelAlertOrder $order) => $order->company ?: trim($order->first_name.' '.$order->last_name)],
            'plugin-client' => ['model' => PluginClient::class, 'label' => 'Plugin-Kunde', 'route' => 'adminv2.customer-management.plugin-clients.edit', 'title' => fn (PluginClient $client) => $client->company_name],
            'api-client' => ['model' => ApiClient::class, 'label' => 'API-Kunde', 'route' => 'adminv2.customer-management.api-clients.edit', 'title' => fn (ApiClient $client) => $client->name],
        ];
    }

    /**
     * Art eines Datensatzes bzw. eines gespeicherten subject_type; null, wenn sich an ihn keine Aufgabe haengen laesst.
     */
    public static function kindOf(Model|string|null $subject): ?string
    {
        if ($subject === null || $subject === '') {
            return null;
        }

        foreach (self::all() as $kind => $definition) {
            if ($subject instanceof Model ? $subject instanceof $definition['model'] : (new $definition['model'])->getMorphClass() === $subject) {
                return $kind;
            }
        }

        return null;
    }

    /**
     * Der Datensatz zu Art und ID – auch aus dem Papierkorb.
     */
    public static function find(?string $kind, ?int $id): ?Model
    {
        $definition = self::all()[$kind ?? ''] ?? null;

        if (! $definition || ! $id) {
            return null;
        }

        $query = $definition['model']::query();

        if (in_array(SoftDeletes::class, class_uses_recursive($definition['model']), true)) {
            $query->withTrashed();
        }

        return $query->find($id);
    }

    /**
     * Wert fuer subject_type an der Aufgabe.
     */
    public static function morphClass(?string $kind): ?string
    {
        $definition = self::all()[$kind ?? ''] ?? null;

        return $definition ? (new $definition['model'])->getMorphClass() : null;
    }

    /**
     * Rubrik, die bei einer neuen Aufgabe zu dieser Art vorbelegt ist.
     */
    public static function category(?string $kind): ?string
    {
        return self::all()[$kind ?? '']['category'] ?? null;
    }

    /**
     * Bezeichnung der Art, z. B. "Airline".
     */
    public static function kindLabel(?string $kind): ?string
    {
        return self::all()[$kind ?? '']['label'] ?? null;
    }

    /**
     * Woran man den Datensatz erkennt, ohne die Art – z. B. "Eurowings (EW)".
     */
    public static function title(?Model $subject): ?string
    {
        $kind = self::kindOf($subject);

        if (! $kind) {
            return null;
        }

        $title = trim((string) self::all()[$kind]['title']($subject));

        return $title !== '' ? $title : '#'.$subject->getKey();
    }

    /**
     * Kurze Bezeichnung des Datensatzes, z. B. "Airline: Eurowings (EW)".
     */
    public static function label(?Model $subject): ?string
    {
        $kind = self::kindOf($subject);

        if (! $kind) {
            return null;
        }

        $definition = self::all()[$kind];
        $title = trim((string) $definition['title']($subject));

        return $definition['label'].': '.($title !== '' ? $title : ($kind === 'event' ? 'Ohne Titel' : '#'.$subject->getKey()));
    }

    /**
     * Seite, auf der der Datensatz bearbeitet wird.
     */
    public static function url(?Model $subject): ?string
    {
        $kind = self::kindOf($subject);

        return $kind ? route(self::all()[$kind]['route'], $subject->getKey()) : null;
    }
}
