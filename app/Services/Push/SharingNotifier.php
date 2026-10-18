<?php

namespace App\Services\Push;

use App\Models\Push\AppDevice;
use App\Models\Push\AppPushMessage;

/**
 * Push an Geraete beim Teilen in der App: Einladung zu einer Reise oder Gruppe, neue Reise in einer Gruppe.
 * Die App laedt daraufhin ihre Freigaben neu (data.type = share). Nur erreichbare Geraete; ohne Empfaenger kein Versand.
 */
class SharingNotifier
{
    public function __construct(private PushDispatcher $dispatcher) {}

    public function tripInvite(AppDevice $from, string $tripName, array $deviceIds): void
    {
        $name = $from->publicName();
        $this->send($deviceIds, 'trip_invite', [
            'de' => 'Einladung zu einer Reise',
            'en' => 'Trip invitation',
            'nl' => 'Uitnodiging voor een reis',
        ], [
            'de' => "{$name} möchte die Reise „{$tripName}“ mit dir teilen.",
            'en' => "{$name} wants to share the trip “{$tripName}” with you.",
            'nl' => "{$name} wil de reis ‘{$tripName}’ met je delen.",
        ]);
    }

    public function groupTrip(AppDevice $from, string $tripName, string $groupName, array $deviceIds): void
    {
        $name = $from->publicName();
        $this->send($deviceIds, 'group_trip', [
            'de' => $groupName,
            'en' => $groupName,
            'nl' => $groupName,
        ], [
            'de' => "{$name} hat die Reise „{$tripName}“ mit der Gruppe geteilt.",
            'en' => "{$name} shared the trip “{$tripName}” with the group.",
            'nl' => "{$name} heeft de reis ‘{$tripName}’ met de groep gedeeld.",
        ]);
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

    private function send(array $deviceIds, string $event, array $title, array $body): void
    {
        $ids = AppDevice::reachable()->whereIn('id', array_values(array_unique($deviceIds)))->pluck('id')->all();
        if ($ids === []) {
            return;
        }

        $message = $this->dispatcher->begin([
            'type' => AppPushMessage::TYPE_SHARE,
            'source' => AppPushMessage::SOURCE_SHARING,
            'title' => $title,
            'body' => $body,
            'data' => ['event' => $event],
            'target' => ['device_ids' => $ids],
        ]);
        $this->dispatcher->dispatch($message, $ids);
        $this->dispatcher->release($message);
    }
}
