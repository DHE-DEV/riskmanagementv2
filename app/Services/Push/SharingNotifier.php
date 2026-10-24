<?php

namespace App\Services\Push;

use App\Jobs\Push\SendSharingSync;
use App\Models\Push\AppDevice;
use App\Models\Push\AppDeviceTransfer;
use App\Models\Push\AppGroupMember;
use App\Models\Push\AppPushMessage;
use App\Models\Push\AppSharedTrip;

/**
 * Push an Geraete beim Teilen in der App: Einladung zu einer Reise oder Gruppe, neue Reise in einer Gruppe,
 * geaenderter Reiseverlauf.
 * Die App laedt daraufhin ihre Freigaben neu (data.type = share). Nur erreichbare Geraete; ohne Empfaenger kein Versand.
 */
class SharingNotifier
{
    public function __construct(private PushDispatcher $dispatcher) {}

    public function tripInvite(AppDevice $from, AppSharedTrip $trip, array $deviceIds): void
    {
        $name = $from->publicName();
        $tripName = $trip->name;
        $this->send($deviceIds, 'trip_invite', [
            'de' => 'Einladung zu einer Reise',
            'en' => 'Trip invitation',
            'nl' => 'Uitnodiging voor een reis',
        ], [
            'de' => "{$name} möchte die Reise „{$tripName}“ mit dir teilen.",
            'en' => "{$name} wants to share the trip “{$tripName}” with you.",
            'nl' => "{$name} wil de reis ‘{$tripName}’ met je delen.",
        ], ['trip' => $trip->id]);
    }

    public function groupTrip(AppDevice $from, AppSharedTrip $trip, string $groupName, array $deviceIds): void
    {
        $name = $from->publicName();
        $tripName = $trip->name;
        $this->send($deviceIds, 'group_trip', [
            'de' => $groupName,
            'en' => $groupName,
            'nl' => $groupName,
        ], [
            'de' => "{$name} hat die Reise „{$tripName}“ mit der Gruppe geteilt.",
            'en' => "{$name} shared the trip “{$tripName}” with the group.",
            'nl' => "{$name} heeft de reis ‘{$tripName}’ met de groep gedeeld.",
        ], ['trip' => $trip->id]);
    }

    public function groupInvite(AppDevice $from, string $groupName, array $deviceIds): void
    {
        $name = $from->publicName();
        $this->send($deviceIds, 'group_invite', [
            'de' => 'Einladung zu einer Gruppe',
            'en' => 'Group invitation',
            'nl' => 'Uitnodiging voor een groep',
        ], [
            'de' => "{$name} lädt dich in die Gruppe „{$groupName}“ ein.",
            'en' => "{$name} invites you to the group “{$groupName}”.",
            'nl' => "{$name} nodigt je uit voor de groep ‘{$groupName}’.",
        ]);
    }

    /**
     * Stiller Push nach einem geaenderten Anzeigenamen: an alle, die mit dem Geraet eine Gruppe (angenommen) oder eine
     * geteilte Reise haben – ihre App laedt neu und zeigt den Namen statt der App-ID.
     */
    public function nameChanged(AppDevice $device): void
    {
        $groupIds = AppGroupMember::where('app_device_id', $device->id)->where('status', AppGroupMember::STATUS_ACCEPTED)->pluck('app_group_id');
        $ids = AppGroupMember::whereIn('app_group_id', $groupIds)->where('status', AppGroupMember::STATUS_ACCEPTED)->pluck('app_device_id');
        $trips = AppSharedTrip::where('owner_device_id', $device->id)
            ->orWhereHas('recipients', fn ($q) => $q->where('app_device_id', $device->id))
            ->orWhereHas('groups', fn ($q) => $q->whereIn('app_groups.id', $groupIds->all() ?: ['-']))
            ->get();
        foreach ($trips as $trip) {
            $ids = $ids->merge($trip->participants()->pluck('id'));
        }
        // Auch Gruppen, in die das Geraet erst eingeladen ist: der Ersteller sieht die Einladung mit Namen.
        $ids = $ids->merge(AppGroupMember::whereIn('app_group_id', AppGroupMember::where('app_device_id', $device->id)->pluck('app_group_id'))
            ->whereHas('group', fn ($q) => $q->whereColumn('app_groups.owner_device_id', 'app_group_members.app_device_id'))->pluck('app_device_id'));
        $ids = $ids->unique()->reject(fn ($id) => $id === $device->id)->values()->all();
        if ($ids !== []) {
            SendSharingSync::dispatch('', $ids);
        }
    }

    /** Stiller Push an alle anderen Teilnehmer der Reise: Stand neu laden. */
    public function sync(AppSharedTrip $trip, AppDevice $from): void
    {
        $ids = $trip->participants()->pluck('id')->reject(fn ($id) => $id === $from->id)->values()->all();
        if ($ids !== []) {
            SendSharingSync::dispatch($trip->id, $ids);
        }
    }

    /**
     * Sichtbarer Hinweis an die anderen Teilnehmer, wenn sich im Reiseverlauf Ort, Tag oder Zeiten geaendert haben
     * (Notizen allein nicht). Nur an Geraete, die das nicht abgeschaltet haben (notify_itinerary).
     *
     * @param  array<int, array<string, mixed>>  $stays  geaenderte Aufenthalte (geloeschte mit deleted)
     */
    public function itineraryChanged(AppSharedTrip $trip, AppDevice $from, array $stays): void
    {
        $ids = $trip->participants()->where('notify_itinerary', '!==', false)->pluck('id')->reject(fn ($id) => $id === $from->id)->values()->all();
        if ($ids === [] || $stays === []) {
            return;
        }
        $name = $from->publicName();
        $title = ['de' => $trip->name, 'en' => $trip->name, 'nl' => $trip->name];

        if (count($stays) > 1) {
            $count = count($stays);
            $body = [
                'de' => "{$name} hat den Reiseverlauf geändert ({$count} Einträge).",
                'en' => "{$name} changed the itinerary ({$count} entries).",
                'nl' => "{$name} heeft de reisroute gewijzigd ({$count} items).",
            ];
        } else {
            $stay = $stays[0];
            $day = $this->dayLabel($trip, $stay['from']);
            $place = [
                'de' => $stay['kind'] === 'sea' ? 'Seetag' : $stay['name'],
                'en' => $stay['kind'] === 'sea' ? 'Day at sea' : $stay['name'],
                'nl' => $stay['kind'] === 'sea' ? 'Zeedag' : $stay['name'],
            ];
            $times = $this->timesLabel($stay);
            $body = ($stay['deleted'] ?? false) ? [
                'de' => "{$name} hat {$day['de']} ({$place['de']}) aus dem Reiseverlauf entfernt.",
                'en' => "{$name} removed {$day['en']} ({$place['en']}) from the itinerary.",
                'nl' => "{$name} heeft {$day['nl']} ({$place['nl']}) uit de reisroute verwijderd.",
            ] : [
                'de' => "{$name} hat {$day['de']} geändert: {$place['de']}{$times['de']}",
                'en' => "{$name} changed {$day['en']}: {$place['en']}{$times['en']}",
                'nl' => "{$name} heeft {$day['nl']} gewijzigd: {$place['nl']}{$times['nl']}",
            ];
        }

        $this->send($ids, 'itinerary', $title, $body, ['trip' => $trip->id]);
    }

    public const TRIP_CHANGES = ['name', 'description', 'travel_type', 'dates', 'countries'];

    /**
     * Sichtbarer Hinweis an die anderen Teilnehmer, wenn der Ersteller das moechte: was sich an der Reise geaendert hat
     * („Anna hat „Kanaren“ geändert: Zeitraum und Beschreibung.“). Nicht an Geraete, die Aenderungen an geteilten
     * Reisen abgeschaltet haben (notify_itinerary). Liefert die Zahl der Empfaenger.
     *
     * @param  array<int, string>  $changes
     */
    public function tripChanged(AppSharedTrip $trip, AppDevice $from, array $changes): int
    {
        $ids = $trip->participants()->where('notify_itinerary', '!==', false)->pluck('id')->reject(fn ($id) => $id === $from->id)->values()->all();
        $reachable = AppDevice::reachable()->whereIn('id', $ids)->count();
        if ($ids === [] || $changes === []) {
            return 0;
        }
        $labels = [
            'de' => ['name' => 'Name', 'description' => 'Beschreibung', 'travel_type' => 'Reiseart', 'dates' => 'Zeitraum', 'countries' => 'Reiseländer'],
            'en' => ['name' => 'name', 'description' => 'description', 'travel_type' => 'type of trip', 'dates' => 'dates', 'countries' => 'countries'],
            'nl' => ['name' => 'naam', 'description' => 'beschrijving', 'travel_type' => 'reistype', 'dates' => 'periode', 'countries' => 'landen'],
        ];
        $and = ['de' => 'und', 'en' => 'and', 'nl' => 'en'];
        $list = function (string $lang) use ($changes, $labels, $and): string {
            $words = array_map(fn ($c) => $labels[$lang][$c], $changes);
            $last = array_pop($words);

            return $words === [] ? $last : implode(', ', $words).' '.$and[$lang].' '.$last;
        };
        $name = $from->publicName();
        $this->send($ids, 'trip_changed', ['de' => $trip->name, 'en' => $trip->name, 'nl' => $trip->name], [
            'de' => "{$name} hat die Reise geändert: ".$list('de').'.',
            'en' => "{$name} changed the trip: ".$list('en').'.',
            'nl' => "{$name} heeft de reis gewijzigd: ".$list('nl').'.',
        ], ['trip' => $trip->id]);

        return $reachable;
    }

    /**
     * Sichtbarer Hinweis an das alte Geraet: ein neues Geraet moechte seine Daten uebernehmen
     * (data.type = transfer_request, data.transfer = ID der Anfrage). Die App zeigt die Anfrage zum Bestaetigen.
     */
    public function transferRequest(AppDevice $from, AppDeviceTransfer $transfer): void
    {
        $label = filled($transfer->to_label) ? $transfer->to_label : null;
        $this->send([$from->id], 'transfer_request', [
            'de' => 'Datenübernahme angefragt',
            'en' => 'Data transfer requested',
            'nl' => 'Gegevensoverdracht aangevraagd',
        ], [
            'de' => 'Ein neues Gerät'.($label ? " ({$label})" : '').' möchte deine TripData-Daten übernehmen. Öffne TripData, um zu bestätigen.',
            'en' => 'A new device'.($label ? " ({$label})" : '').' wants to take over your TripData data. Open TripData to confirm.',
            'nl' => 'Een nieuw apparaat'.($label ? " ({$label})" : '').' wil je TripData-gegevens overnemen. Open TripData om te bevestigen.',
        ], ['transfer' => $transfer->id], AppPushMessage::TYPE_TRANSFER_REQUEST, AppPushMessage::SOURCE_TRANSFER);
    }

    /** „Tag 5“ ab Reisebeginn, ohne Startdatum das Datum („9.11.“). */
    private function dayLabel(AppSharedTrip $trip, string $date): array
    {
        $day = \Illuminate\Support\Carbon::parse($date);
        if ($trip->start_date && $day->gte($trip->start_date)) {
            $n = (int) $trip->start_date->diffInDays($day) + 1;

            return ['de' => "Tag {$n}", 'en' => "day {$n}", 'nl' => "dag {$n}"];
        }
        $label = $day->format('j.n.');

        return ['de' => "den {$label}", 'en' => $day->format('M j'), 'nl' => $label];
    }

    /** ", 08:00–18:00" bzw. ", an 07:00" / ", ab 22:30" je Sprache; leer ohne Zeiten. */
    private function timesLabel(array $stay): array
    {
        $arrival = $stay['arrival'] ?? null;
        $departure = $stay['departure'] ?? null;

        return match (true) {
            $arrival && $departure => array_fill_keys(['de', 'en', 'nl'], ", {$arrival}–{$departure}"),
            (bool) $arrival => ['de' => ", an {$arrival}", 'en' => ", arr. {$arrival}", 'nl' => ", aank. {$arrival}"],
            (bool) $departure => ['de' => ", ab {$departure}", 'en' => ", dep. {$departure}", 'nl' => ", vertr. {$departure}"],
            default => array_fill_keys(['de', 'en', 'nl'], ''),
        };
    }

    private function send(array $deviceIds, string $event, array $title, array $body, array $data = [], string $type = AppPushMessage::TYPE_SHARE, string $source = AppPushMessage::SOURCE_SHARING): void
    {
        $ids = AppDevice::reachable()->whereIn('id', array_values(array_unique($deviceIds)))->pluck('id')->all();
        if ($ids === []) {
            return;
        }

        $message = $this->dispatcher->begin([
            'type' => $type,
            'source' => $source,
            'title' => $title,
            'body' => $body,
            'data' => ['event' => $event] + $data,
            'target' => ['device_ids' => $ids],
        ]);
        $this->dispatcher->dispatch($message, $ids);
        $this->dispatcher->release($message);
    }
}
