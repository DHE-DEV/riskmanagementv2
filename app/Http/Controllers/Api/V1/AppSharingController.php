<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Push\AppDevice;
use App\Models\Push\AppDeviceTravelLink;
use App\Models\Push\AppGroup;
use App\Models\Push\AppGroupMember;
use App\Models\Push\AppInvite;
use App\Models\Push\AppSharedList;
use App\Models\Push\AppSharedTrip;
use App\Models\Push\AppSharedTripRecipient;
use App\Services\Push\SharingNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Teilen in der TripData-App (alles mit X-Device-Secret, unter /v1/devices/{id}):
 *
 *   GET    sharing                          eigene App-ID, Gruppen, Einladungen, empfangene und geteilte Reisen
 *   GET    lookup/{code}                    Name zu einer App-ID (vor dem Einladen)
 *   POST   groups                           Gruppe anlegen {name}
 *   PUT    groups/{group}                   umbenennen (Ersteller)
 *   DELETE groups/{group}                   aufloesen (Ersteller)
 *   POST   groups/{group}/members           einladen {codes: []} (Ersteller)
 *   DELETE groups/{group}/members/{member}  entfernen (Ersteller) bzw. selbst verlassen
 *   POST   groups/{group}/accept|decline    Einladung annehmen/ablehnen
 *   PUT    trips/{clientTripId}             eigene Reise teilen bzw. aktualisieren {name, …, codes?, group_ids?}
 *   DELETE trips/{clientTripId}             Freigabe aufheben
 *   POST   shared-trips/{trip}/accept|decline  empfangene Reise annehmen / ablehnen bzw. ausblenden
 *   PUT    trips/{clientTripId}/sights      Sehenswuerdigkeiten der eigenen Reise gemeinsam nutzen bzw. Aenderungen senden
 *   DELETE trips/{clientTripId}/sights      Sehenswuerdigkeiten nicht mehr gemeinsam nutzen
 *   PUT    shared-trips/{trip}/sights       Aenderungen an den gemeinsamen Sehenswuerdigkeiten einer empfangenen Reise
 *   PUT    trips/{clientTripId}/list        Packliste der eigenen Reise teilen bzw. Inhalt und Rechte setzen
 *   DELETE trips/{clientTripId}/list        Packliste nicht mehr teilen
 *   PUT    shared-lists/{list}              Aenderungen an einer geteilten Packliste (je nach Rolle)
 *   PUT    shared-lists/{list}/roles        Rechte festlegen (Ersteller und „verwalten“)
 *   POST   trips/{clientTripId}/invite      Einladungslink zur eigenen geteilten Reise ({renew: true} = neuer Code)
 *   POST   groups/{group}/invite            Einladungslink zur Gruppe (Ersteller)
 *   GET    invites/{code}                   Einladung ansehen
 *   POST   invites/{code}/accept            Einladung annehmen (Reise: angenommener Empfaenger, Gruppe: Mitglied)
 *
 * Nach jeder Aenderung an einer geteilten Reise (Inhalt, Packliste, Sehenswuerdigkeiten, Teilnehmer) bekommen die
 * anderen Teilnehmer einen stillen Push (data.type = share_sync), damit ihre App sofort neu laedt.
 */
class AppSharingController extends Controller
{
    public function __construct(private SharingNotifier $notifier) {}

    public function show(Request $request, string $id): JsonResponse
    {
        $device = $this->device($request, $id);
        $device->ensureShareCode();

        return response()->json(['success' => true, 'data' => $this->state($device)]);
    }

    public function lookup(Request $request, string $id, string $code): JsonResponse
    {
        $this->device($request, $id);
        $other = AppDevice::where('share_code', AppDevice::normalizeShareCode($code) ?? '-')->first();
        abort_if(! $other, 404, 'Unknown app ID.');

        return response()->json(['success' => true, 'data' => [
            'share_code' => AppDevice::formatShareCode($other->share_code),
            'name' => $other->publicName(),
        ]]);
    }

    // MARK: Gruppen

    public function storeGroup(Request $request, string $id): JsonResponse
    {
        $device = $this->device($request, $id);
        $data = $request->validate(['name' => ['required', 'string', 'max:60']]);
        abort_if(AppGroup::where('owner_device_id', $device->id)->count() >= AppGroup::MAX_OWNED, 422, 'Too many groups.');

        $group = DB::transaction(function () use ($device, $data) {
            $group = AppGroup::create(['owner_device_id' => $device->id, 'name' => trim($data['name'])]);
            $group->members()->create(['app_device_id' => $device->id, 'status' => AppGroupMember::STATUS_ACCEPTED]);

            return $group;
        });

        return response()->json(['success' => true, 'data' => ['id' => $group->id] + $this->state($device)], 201);
    }

    public function updateGroup(Request $request, string $id, string $group): JsonResponse
    {
        $device = $this->device($request, $id);
        $group = $this->ownedGroup($device, $group);
        $group->update($request->validate(['name' => ['required', 'string', 'max:60']]));

        return response()->json(['success' => true, 'data' => $this->state($device)]);
    }

    public function destroyGroup(Request $request, string $id, string $group): JsonResponse
    {
        $device = $this->device($request, $id);
        $this->ownedGroup($device, $group)->delete();

        return response()->json(['success' => true, 'data' => $this->state($device)]);
    }

    public function inviteMembers(Request $request, string $id, string $group): JsonResponse
    {
        $device = $this->device($request, $id);
        $group = $this->ownedGroup($device, $group);
        $data = $request->validate(['codes' => ['required', 'array', 'min:1', 'max:'.AppGroup::MAX_MEMBERS], 'codes.*' => ['string', 'max:16']]);
        $others = $this->devicesForCodes($data['codes'], $device);

        abort_if($group->members()->count() + $others->count() > AppGroup::MAX_MEMBERS, 422, 'Too many members.');

        $new = [];
        foreach ($others as $other) {
            $member = $group->members()->firstOrCreate(['app_device_id' => $other->id], ['status' => AppGroupMember::STATUS_INVITED]);
            if ($member->wasRecentlyCreated) {
                $new[] = $other->id;
            }
        }
        $this->notifier->groupInvite($device, $group->name, $new);

        return response()->json(['success' => true, 'data' => $this->state($device)]);
    }

    public function removeMember(Request $request, string $id, string $group, int $member): JsonResponse
    {
        $device = $this->device($request, $id);
        $group = AppGroup::findOrFail($group);
        $member = $group->members()->findOrFail($member);

        $self = $member->app_device_id === $device->id;
        abort_unless($self || $group->owner_device_id === $device->id, 403, 'Only the owner can remove members.');
        abort_if($self && $group->owner_device_id === $device->id, 422, 'The owner cannot leave; delete the group instead.');
        $member->delete();

        return response()->json(['success' => true, 'data' => $this->state($device)]);
    }

    public function acceptGroup(Request $request, string $id, string $group): JsonResponse
    {
        $device = $this->device($request, $id);
        $this->membership($device, $group)->update(['status' => AppGroupMember::STATUS_ACCEPTED]);

        return response()->json(['success' => true, 'data' => $this->state($device)]);
    }

    public function declineGroup(Request $request, string $id, string $group): JsonResponse
    {
        $device = $this->device($request, $id);
        $member = $this->membership($device, $group);
        abort_if($member->group->owner_device_id === $device->id, 422, 'The owner cannot leave; delete the group instead.');
        $member->delete();

        return response()->json(['success' => true, 'data' => $this->state($device)]);
    }

    // MARK: Eigene Reisen teilen

    public function upsertTrip(Request $request, string $id, string $clientTripId): JsonResponse
    {
        $device = $this->device($request, $id);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
            'country_codes' => ['present', 'array', 'max:40'],
            'country_codes.*' => ['string', 'size:2'],
            'travel_link_code' => ['nullable', 'string', 'max:20'],
            'codes' => ['sometimes', 'array', 'max:'.AppSharedTrip::MAX_RECIPIENTS],
            'codes.*' => ['string', 'max:16'],
            'group_ids' => ['sometimes', 'array', 'max:20'],
            'group_ids.*' => ['string', 'max:32'],
        ]);
        abort_if(strlen($clientTripId) > 64, 422, 'Invalid trip id.');

        $recipients = array_key_exists('codes', $data) ? $this->devicesForCodes($data['codes'], $device) : null;
        $groups = null;
        if (array_key_exists('group_ids', $data)) {
            // Nur Gruppen, in denen das Geraet angenommenes Mitglied ist.
            $groups = AppGroup::whereIn('id', $data['group_ids'])
                ->whereHas('members', fn ($q) => $q->where('app_device_id', $device->id)->where('status', AppGroupMember::STATUS_ACCEPTED))
                ->get();
        }

        [$trip, $invited, $newGroups] = DB::transaction(function () use ($device, $clientTripId, $data, $recipients, $groups) {
            $fields = [
                'name' => trim($data['name']),
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'country_codes' => array_values(array_unique(array_map('strtoupper', $data['country_codes']))),
                'tid' => AppDeviceTravelLink::normalizeTid($data['travel_link_code'] ?? null),
            ];
            $trip = AppSharedTrip::firstOrNew(['owner_device_id' => $device->id, 'client_trip_id' => $clientTripId]);
            $snapshot = fn () => [$trip->name, $trip->start_date?->toDateString(), $trip->end_date?->toDateString(), $trip->country_codes, $trip->tid];
            $before = $trip->exists ? $snapshot() : null;
            $trip->fill($fields);
            if ($before !== null && $before !== $snapshot()) {
                $trip->revision++;
            }
            $trip->save();

            $invited = [];
            if ($recipients !== null) {
                $ids = $recipients->pluck('id')->all();
                $trip->recipients()->where('direct', true)->whereNotIn('app_device_id', $ids ?: ['-'])->delete();
                foreach ($ids as $deviceId) {
                    $row = $trip->recipients()->firstOrNew(['app_device_id' => $deviceId]);
                    if (! $row->exists || ! $row->direct) {
                        $row->fill(['status' => AppSharedTripRecipient::STATUS_INVITED, 'direct' => true])->save();
                        $invited[] = $deviceId;
                    }
                }
            }

            $newGroups = [];
            if ($groups !== null) {
                $known = $trip->groups()->pluck('app_groups.id')->all();
                $trip->groups()->sync($groups->mapWithKeys(fn ($g) => [$g->id => ['created_at' => now()]])->all());
                $newGroups = $groups->reject(fn ($g) => in_array($g->id, $known, true))->values();
            }

            return [$trip, $invited, $newGroups];
        });

        $this->notifier->tripInvite($device, $trip->name, $invited);
        $this->notifier->sync($trip, $device);
        foreach ($newGroups as $group) {
            $this->notifier->groupTrip($device, $trip->name, $group->name, array_diff($group->acceptedDeviceIds(), [$device->id]));
        }

        return response()->json(['success' => true, 'data' => $this->state($device)]);
    }

    public function destroyTrip(Request $request, string $id, string $clientTripId): JsonResponse
    {
        $device = $this->device($request, $id);
        $trip = AppSharedTrip::where('owner_device_id', $device->id)->where('client_trip_id', $clientTripId)->first();
        if ($trip) {
            $this->notifier->sync($trip, $device);
            $trip->delete();
        }

        return response()->json(['success' => true, 'data' => $this->state($device)]);
    }

    // MARK: Empfangene Reisen

    public function acceptTrip(Request $request, string $id, string $trip): JsonResponse
    {
        $device = $this->device($request, $id);
        $trip = $this->visibleTrip($device, $trip);
        $trip->recipients()->updateOrCreate(['app_device_id' => $device->id], ['status' => AppSharedTripRecipient::STATUS_ACCEPTED]);
        $this->notifier->sync($trip, $device);

        return response()->json(['success' => true, 'data' => $this->state($device)]);
    }

    public function declineTrip(Request $request, string $id, string $trip): JsonResponse
    {
        $device = $this->device($request, $id);
        $trip = $this->visibleTrip($device, $trip);
        $row = $trip->recipients()->firstOrNew(['app_device_id' => $device->id], ['direct' => false]);
        $row->status = AppSharedTripRecipient::STATUS_DECLINED;
        $row->save();
        $this->notifier->sync($trip, $device);

        return response()->json(['success' => true, 'data' => $this->state($device)]);
    }

    // MARK: Geteilte Packliste

    public function upsertList(Request $request, string $id, string $clientTripId): JsonResponse
    {
        $device = $this->device($request, $id);
        $trip = AppSharedTrip::where('owner_device_id', $device->id)->where('client_trip_id', $clientTripId)->firstOrFail();
        $data = $request->validate($this->listRules() + [
            'client_list_id' => ['required', 'string', 'max:64'],
            'default_role' => ['sometimes', 'in:'.implode(',', AppSharedList::ROLES)],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['in:'.implode(',', AppSharedList::ROLES)],
        ]);

        DB::transaction(function () use ($trip, $data) {
            $list = $trip->list()->firstOrNew([], ['suitcases' => [], 'items' => [], 'name' => $data['name']]);
            $list->client_list_id = $data['client_list_id'];
            $changed = $list->merge('owner', $data['name'], $data['suitcases'] ?? [], $data['items'] ?? []);
            $this->applyRoles($list, $data, $trip);
            if ($list->exists && ($changed || $list->isDirty())) {
                $list->revision++;
            }
            $list->setRelation('trip', $trip);
            $list->save();
        });
        $this->notifier->sync($trip, $device);

        return response()->json(['success' => true, 'data' => $this->state($device)]);
    }

    public function destroyList(Request $request, string $id, string $clientTripId): JsonResponse
    {
        $device = $this->device($request, $id);
        $trip = AppSharedTrip::where('owner_device_id', $device->id)->where('client_trip_id', $clientTripId)->firstOrFail();
        $trip->list()->delete();
        $this->notifier->sync($trip, $device);

        return response()->json(['success' => true, 'data' => $this->state($device)]);
    }

    public function updateList(Request $request, string $id, string $list): JsonResponse
    {
        $device = $this->device($request, $id);
        $list = $this->visibleList($device, $list);
        $data = $request->validate($this->listRules());
        $role = $list->roleFor($device);
        abort_if($role === AppSharedList::ROLE_VIEW, 403, 'This list is read-only for you.');

        DB::transaction(function () use ($list, $data, $role, $device) {
            $list->refresh();
            if ($list->merge($role, $data['name'] ?? null, $data['suitcases'] ?? [], $data['items'] ?? [])) {
                $list->revision++;
                $list->save();
                $this->notifier->sync($list->trip, $device);
            }
        });

        return response()->json(['success' => true, 'data' => $this->state($device)]);
    }

    public function updateListRoles(Request $request, string $id, string $list): JsonResponse
    {
        $device = $this->device($request, $id);
        $list = $this->visibleList($device, $list);
        abort_unless(in_array($list->roleFor($device), ['owner', AppSharedList::ROLE_MANAGE], true), 403, 'Only the owner or managers can change rights.');
        $data = $request->validate([
            'default_role' => ['sometimes', 'in:'.implode(',', AppSharedList::ROLES)],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['in:'.implode(',', AppSharedList::ROLES)],
        ]);
        $this->applyRoles($list, $data, $list->trip);
        if ($list->isDirty()) {
            $list->revision++;
            $list->save();
            $this->notifier->sync($list->trip, $device);
        }

        return response()->json(['success' => true, 'data' => $this->state($device)]);
    }

    // MARK: Gemeinsame Sehenswuerdigkeiten

    public function upsertSights(Request $request, string $id, string $clientTripId): JsonResponse
    {
        $device = $this->device($request, $id);
        $trip = AppSharedTrip::where('owner_device_id', $device->id)->where('client_trip_id', $clientTripId)->firstOrFail();
        $this->mergeSights($request, $device, $trip, start: true);

        return response()->json(['success' => true, 'data' => $this->state($device)]);
    }

    public function destroySights(Request $request, string $id, string $clientTripId): JsonResponse
    {
        $device = $this->device($request, $id);
        $trip = AppSharedTrip::where('owner_device_id', $device->id)->where('client_trip_id', $clientTripId)->firstOrFail();
        $trip->update(['sights' => null, 'sight_visits' => null]);
        $this->notifier->sync($trip, $device);

        return response()->json(['success' => true, 'data' => $this->state($device)]);
    }

    public function updateSights(Request $request, string $id, string $trip): JsonResponse
    {
        $device = $this->device($request, $id);
        $trip = $this->visibleTrip($device, $trip);
        abort_unless($trip->participants()->contains('id', $device->id), 404);
        abort_unless($trip->sharesSights(), 409, 'The sights of this trip are not shared.');
        $this->mergeSights($request, $device, $trip, start: false);

        return response()->json(['success' => true, 'data' => $this->state($device)]);
    }

    private function mergeSights(Request $request, AppDevice $device, AppSharedTrip $trip, bool $start): void
    {
        $data = $request->validate([
            'sights' => ['present', 'array', 'max:'.AppSharedTrip::MAX_SIGHTS * 2],
            'sights.*.key' => ['required', 'string', 'regex:/^sight:\d+$/'],
            'sights.*.updated_at' => ['required', 'numeric', 'min:0'],
            'sights.*.deleted' => ['sometimes', 'boolean'],
            'visited' => ['sometimes', 'nullable', 'array', 'max:'.AppSharedTrip::MAX_SIGHTS * 2],
            'visited.*' => ['string', 'max:32'],
        ]);

        $changed = DB::transaction(function () use ($trip, $device, $data, $start) {
            $trip->refresh();
            $starting = $start && ! $trip->sharesSights();
            if ($starting) {
                $trip->sights = [];
            }
            $changed = $trip->mergeSights($device, $data['sights'], $data['visited'] ?? null) || $starting;
            if ($changed) {
                $trip->save();
            }

            return $changed;
        });
        if ($changed) {
            $this->notifier->sync($trip, $device);
        }
    }

    private function listRules(): array
    {
        return [
            'name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'suitcases' => ['sometimes', 'array', 'max:'.AppSharedList::MAX_SUITCASES * 2],
            'items' => ['sometimes', 'array', 'max:'.AppSharedList::MAX_ITEMS * 2],
        ];
    }

    /** Rollen aus {App-ID: Rolle} – nur fuer Teilnehmer der Reise; der Ersteller hat keine Rolle. */
    private function applyRoles(AppSharedList $list, array $data, AppSharedTrip $trip): void
    {
        if (isset($data['default_role'])) {
            $list->default_role = $data['default_role'];
        }
        if (array_key_exists('roles', $data)) {
            $byCode = $trip->participants()->keyBy('share_code');
            $roles = [];
            foreach ((array) $data['roles'] as $code => $role) {
                $device = $byCode->get(AppDevice::normalizeShareCode((string) $code) ?? '-');
                if ($device && $device->id !== $trip->owner_device_id) {
                    $roles[$device->id] = $role;
                }
            }
            $list->roles = $roles ?: null;
        }
    }

    private function visibleList(AppDevice $device, string $list): AppSharedList
    {
        $list = AppSharedList::with('trip')->findOrFail($list);
        abort_unless($list->trip->participants()->contains('id', $device->id), 404);

        return $list;
    }

    /** Teilnehmer einer Reise fuer die App (Koffer zuordnen, Rechte vergeben). */
    private function participantsApi(AppSharedTrip $trip, AppDevice $device, ?AppSharedList $list): array
    {
        return $trip->participants()->map(fn (AppDevice $p) => [
            'share_code' => AppDevice::formatShareCode($p->share_code),
            'name' => $p->publicName(),
            'is_owner' => $p->id === $trip->owner_device_id,
            'is_me' => $p->id === $device->id,
            'list_role' => $list ? $list->roleFor($p) : null,
        ])->all();
    }

    private function listApi(?AppSharedList $list, AppSharedTrip $trip, AppDevice $device): ?array
    {
        if (! $list) {
            return null;
        }
        $list->setRelation('trip', $trip);
        $names = $trip->participants()->mapWithKeys(fn (AppDevice $p) => [$p->share_code => $p->publicName()])->all();

        return $list->toApi($device, $names) + ['default_role' => $list->default_role];
    }

    // MARK: Einladungslinks

    public function tripInvite(Request $request, string $id, string $clientTripId): JsonResponse
    {
        $device = $this->device($request, $id);
        $trip = AppSharedTrip::where('owner_device_id', $device->id)->where('client_trip_id', $clientTripId)->firstOrFail();
        $invite = AppInvite::for($device, $trip, null, (bool) $request->boolean('renew'));

        return response()->json(['success' => true, 'data' => $invite->toApi() + ['preview' => $invite->preview()]]);
    }

    public function groupInvite(Request $request, string $id, string $group): JsonResponse
    {
        $device = $this->device($request, $id);
        $group = $this->ownedGroup($device, $group);
        $invite = AppInvite::for($device, null, $group, (bool) $request->boolean('renew'));

        return response()->json(['success' => true, 'data' => $invite->toApi() + ['preview' => $invite->preview()]]);
    }

    public function showInvite(Request $request, string $id, string $code): JsonResponse
    {
        $device = $this->device($request, $id);
        $invite = $this->validInvite($code);

        return response()->json(['success' => true, 'data' => $invite->preview() + [
            'is_own' => $invite->created_by_device_id === $device->id,
            'already' => $this->alreadyIn($device, $invite),
        ]]);
    }

    public function acceptInvite(Request $request, string $id, string $code): JsonResponse
    {
        $device = $this->device($request, $id);
        $invite = $this->validInvite($code);
        abort_if($invite->created_by_device_id === $device->id, 422, 'This is your own invitation.');

        DB::transaction(function () use ($invite, $device) {
            if ($trip = $invite->trip) {
                abort_if($trip->owner_device_id === $device->id, 422, 'This is your own trip.');
                $trip->recipients()->updateOrCreate(['app_device_id' => $device->id], ['status' => AppSharedTripRecipient::STATUS_ACCEPTED, 'direct' => true]);
            } elseif ($group = $invite->group) {
                abort_if($group->members()->count() >= AppGroup::MAX_MEMBERS, 422, 'Too many members.');
                $group->members()->updateOrCreate(['app_device_id' => $device->id], ['status' => AppGroupMember::STATUS_ACCEPTED]);
            }
            $invite->increment('uses');
        });

        return response()->json(['success' => true, 'data' => ['kind' => $invite->trip ? 'trip' : 'group', 'id' => $invite->app_shared_trip_id ?? $invite->app_group_id] + $this->state($device)]);
    }

    private function validInvite(string $code): AppInvite
    {
        $invite = AppInvite::with(['trip', 'group', 'creator'])->where('code', AppDevice::normalizeShareCode($code) ?? '-')->first();
        abort_if(! $invite || ! $invite->isValid(), 404, 'Invitation not found or expired.');

        return $invite;
    }

    private function alreadyIn(AppDevice $device, AppInvite $invite): bool
    {
        if ($trip = $invite->trip) {
            return $trip->owner_device_id === $device->id
                || $trip->recipients()->where('app_device_id', $device->id)->where('status', AppSharedTripRecipient::STATUS_ACCEPTED)->exists();
        }

        return (bool) $invite->group?->members()->where('app_device_id', $device->id)->where('status', AppGroupMember::STATUS_ACCEPTED)->exists();
    }

    // MARK: Stand fuer die App

    private function state(AppDevice $device): array
    {
        $memberships = AppGroupMember::with(['group.owner', 'group.members.device'])->where('app_device_id', $device->id)->get();
        $acceptedGroupIds = $memberships->where('status', AppGroupMember::STATUS_ACCEPTED)->pluck('app_group_id')->all();

        $groups = $memberships->map(function (AppGroupMember $membership) use ($device) {
            $group = $membership->group;
            $isOwner = $group->owner_device_id === $device->id;
            $accepted = $membership->status === AppGroupMember::STATUS_ACCEPTED;

            return [
                'id' => $group->id,
                'name' => $group->name,
                'status' => $membership->status,
                'is_owner' => $isOwner,
                'owner_name' => $group->owner?->publicName(),
                // Eingeladene sehen nur, wer einlaedt.
                'members' => $accepted ? $group->members->sortBy(fn ($m) => [$m->app_device_id !== $group->owner_device_id, $m->created_at])->values()->map(fn (AppGroupMember $m) => [
                    'id' => $m->id,
                    'name' => $m->device?->publicName(),
                    'share_code' => AppDevice::formatShareCode($m->device?->share_code),
                    'status' => $m->status,
                    'is_owner' => $m->app_device_id === $group->owner_device_id,
                    'is_me' => $m->app_device_id === $device->id,
                ])->all() : [],
            ];
        })->sortBy(fn ($g) => [$g['status'] !== AppGroupMember::STATUS_INVITED, mb_strtolower($g['name'])])->values()->all();

        $ownRows = AppSharedTripRecipient::where('app_device_id', $device->id)->get()->keyBy('app_shared_trip_id');

        // Direkt eingeladen oder ueber eine angenommene Gruppe geteilt – eigene Reisen nicht.
        $visible = AppSharedTrip::with(['owner', 'groups', 'list'])
            ->where('owner_device_id', '!=', $device->id)
            ->where(fn ($q) => $q
                ->whereHas('recipients', fn ($r) => $r->where('app_device_id', $device->id)->where('direct', true))
                ->orWhereHas('groups', fn ($g) => $g->whereIn('app_groups.id', $acceptedGroupIds ?: ['-'])))
            ->get();

        $invitations = [];
        $received = [];
        foreach ($visible as $trip) {
            $row = $ownRows->get($trip->id);
            $viaGroup = $trip->groups->firstWhere(fn ($g) => in_array($g->id, $acceptedGroupIds, true));
            $entry = $trip->toApi() + ['via_group' => $viaGroup?->name, 'sights' => $trip->sightsApi($device)];
            if ($row?->status === AppSharedTripRecipient::STATUS_ACCEPTED || $viaGroup) {
                $entry['participants'] = $this->participantsApi($trip, $device, $trip->list);
                $entry['list'] = $this->listApi($trip->list, $trip, $device);
            }
            if ($row?->status === AppSharedTripRecipient::STATUS_DECLINED) {
                continue;
            }
            if ($row?->status === AppSharedTripRecipient::STATUS_ACCEPTED || ($viaGroup && ! ($row?->direct && $row->status === AppSharedTripRecipient::STATUS_INVITED))) {
                $received[] = $entry;
            } else {
                $invitations[] = $entry;
            }
        }

        $owned = AppSharedTrip::with(['recipients.device', 'groups', 'list'])->where('owner_device_id', $device->id)->get()
            ->map(fn (AppSharedTrip $trip) => [
                'id' => $trip->id,
                'client_trip_id' => $trip->client_trip_id,
                'recipients' => $trip->recipients->where('direct', true)->values()->map(fn (AppSharedTripRecipient $r) => [
                    'name' => $r->device?->publicName(),
                    'share_code' => AppDevice::formatShareCode($r->device?->share_code),
                    'status' => $r->status,
                ])->all(),
                'groups' => $trip->groups->map(fn ($g) => ['id' => $g->id, 'name' => $g->name])->values()->all(),
                'participants' => $this->participantsApi($trip, $device, $trip->list),
                'list' => $this->listApi($trip->list, $trip, $device),
                'sights' => $trip->sightsApi($device),
            ])->values()->all();

        return [
            'me' => ['share_code' => AppDevice::formatShareCode($device->share_code), 'display_name' => $device->display_name],
            'groups' => $groups,
            'trip_invitations' => $invitations,
            'received_trips' => $received,
            'owned_trips' => $owned,
        ];
    }

    // MARK: Hilfen

    private function device(Request $request, string $id): AppDevice
    {
        $device = AppDevice::find($id);
        abort_if(! $device, 404, 'Device not found.');
        abort_unless($device->secretMatches($request->header('X-Device-Secret')), 403, 'Invalid device secret.');

        return $device;
    }

    /** @return Collection<int, AppDevice> */
    private function devicesForCodes(array $codes, AppDevice $self): Collection
    {
        $normalized = [];
        $invalid = [];
        foreach ($codes as $code) {
            $value = AppDevice::normalizeShareCode($code);
            $value ? $normalized[$value] = $value : $invalid[] = $code;
        }
        $devices = AppDevice::whereIn('share_code', array_values($normalized) ?: ['-'])->get();
        $unknown = array_merge($invalid, array_map([AppDevice::class, 'formatShareCode'], array_values(array_diff($normalized, $devices->pluck('share_code')->all()))));
        if ($unknown !== []) {
            throw ValidationException::withMessages(['codes' => ['Unbekannte App-ID: '.implode(', ', $unknown)]]);
        }

        return $devices->reject(fn (AppDevice $d) => $d->id === $self->id)->values();
    }

    private function ownedGroup(AppDevice $device, string $group): AppGroup
    {
        $group = AppGroup::findOrFail($group);
        abort_unless($group->owner_device_id === $device->id, 403, 'Only the owner can change the group.');

        return $group;
    }

    private function membership(AppDevice $device, string $group): AppGroupMember
    {
        return AppGroupMember::with('group')->where('app_group_id', $group)->where('app_device_id', $device->id)->firstOrFail();
    }

    private function visibleTrip(AppDevice $device, string $trip): AppSharedTrip
    {
        $groupIds = AppGroupMember::where('app_device_id', $device->id)->where('status', AppGroupMember::STATUS_ACCEPTED)->pluck('app_group_id')->all();

        return AppSharedTrip::where('id', $trip)
            ->where('owner_device_id', '!=', $device->id)
            ->where(fn ($q) => $q
                ->whereHas('recipients', fn ($r) => $r->where('app_device_id', $device->id))
                ->orWhereHas('groups', fn ($g) => $g->whereIn('app_groups.id', $groupIds ?: ['-'])))
            ->firstOrFail();
    }
}
