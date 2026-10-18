<?php

namespace App\Models\Push;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Gemeinsame Packliste einer geteilten Reise. Rollen: view (nur ansehen), check (abhaken), edit (bearbeiten),
 * manage (bearbeiten und Rechte vergeben). Der Ersteller der Reise darf immer alles.
 *
 * Eintraege (Koffer, Gegenstaende) werden je Eintrag zusammengefuehrt: Inhalt nach `updated_at`,
 * der Haken eines Gegenstands getrennt nach `packed_at` – so geht beim gleichzeitigen Abhaken und
 * Umbenennen nichts verloren. Geloeschte Eintraege bleiben mit `deleted = true` stehen.
 */
class AppSharedList extends Model
{
    use HasUlids;

    public const ROLE_VIEW = 'view';

    public const ROLE_CHECK = 'check';

    public const ROLE_EDIT = 'edit';

    /** Bearbeiten und die Rechte der anderen festlegen (z. B. der zweite Elternteil). */
    public const ROLE_MANAGE = 'manage';

    public const ROLES = [self::ROLE_VIEW, self::ROLE_CHECK, self::ROLE_EDIT, self::ROLE_MANAGE];

    public const MAX_ITEMS = 500;

    public const MAX_SUITCASES = 30;

    protected $fillable = ['app_shared_trip_id', 'client_list_id', 'name', 'suitcases', 'items', 'default_role', 'roles', 'revision'];

    protected function casts(): array
    {
        return [
            'suitcases' => 'array',
            'items' => 'array',
            'roles' => 'array',
            'revision' => 'integer',
        ];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(AppSharedTrip::class, 'app_shared_trip_id');
    }

    public function roleFor(AppDevice $device): string
    {
        if ($this->trip->owner_device_id === $device->id) {
            return 'owner';
        }

        return ((array) $this->roles)[$device->id] ?? $this->default_role;
    }

    /**
     * Aenderungen eines Geraets einarbeiten. true, wenn sich etwas geaendert hat.
     *
     * @param  array<int, array<string, mixed>>  $suitcases
     * @param  array<int, array<string, mixed>>  $items
     */
    public function merge(string $role, ?string $name, array $suitcases, array $items): bool
    {
        $edit = in_array($role, ['owner', self::ROLE_MANAGE, self::ROLE_EDIT], true);
        $check = $edit || $role === self::ROLE_CHECK;
        $changed = false;

        if ($edit && filled($name) && $name !== $this->name) {
            $this->name = mb_substr(trim($name), 0, 120);
            $changed = true;
        }

        $currentSuitcases = $this->keyed((array) $this->suitcases);
        if ($edit) {
            foreach ($suitcases as $incoming) {
                $incoming = self::cleanSuitcase($incoming);
                if (! $incoming) {
                    continue;
                }
                $existing = $currentSuitcases[$incoming['id']] ?? null;
                if (! $existing || $incoming['updated_at'] > ($existing['updated_at'] ?? 0)) {
                    $currentSuitcases[$incoming['id']] = $incoming;
                    $changed = true;
                }
            }
        }

        $currentItems = $this->keyed((array) $this->items);
        foreach ($items as $incoming) {
            $incoming = self::cleanItem($incoming);
            if (! $incoming) {
                continue;
            }
            $existing = $currentItems[$incoming['id']] ?? null;

            if ($edit && (! $existing || $incoming['updated_at'] > ($existing['updated_at'] ?? 0))) {
                // Inhalt uebernehmen, den Haken aber nur, wenn er neuer ist.
                $packed = $existing && ($existing['packed_at'] ?? 0) >= $incoming['packed_at']
                    ? ['packed' => $existing['packed'] ?? false, 'packed_at' => $existing['packed_at'] ?? 0]
                    : ['packed' => $incoming['packed'], 'packed_at' => $incoming['packed_at']];
                $currentItems[$incoming['id']] = array_merge($incoming, $packed);
                $changed = true;
            } elseif ($check && $existing && empty($existing['deleted']) && $incoming['packed_at'] > ($existing['packed_at'] ?? 0)) {
                $currentItems[$incoming['id']] = array_merge($existing, ['packed' => $incoming['packed'], 'packed_at' => $incoming['packed_at']]);
                $changed = true;
            }
        }

        if ($changed) {
            abort_if(count($currentItems) > self::MAX_ITEMS || count($currentSuitcases) > self::MAX_SUITCASES, 422, 'Too many entries.');
            $this->suitcases = array_values($currentSuitcases);
            $this->items = array_values($currentItems);
        }

        return $changed;
    }

    /** @return array<string, array<string, mixed>> */
    private function keyed(array $entries): array
    {
        $result = [];
        foreach ($entries as $entry) {
            if (is_array($entry) && isset($entry['id'])) {
                $result[(string) $entry['id']] = $entry;
            }
        }

        return $result;
    }

    private static function cleanSuitcase(mixed $entry): ?array
    {
        if (! is_array($entry) || ! is_string($entry['id'] ?? null) || strlen($entry['id']) > 64) {
            return null;
        }

        return [
            'id' => $entry['id'],
            'name' => mb_substr((string) ($entry['name'] ?? ''), 0, 60),
            'color' => preg_match('/^[0-9A-Fa-f]{6}$/', (string) ($entry['color'] ?? '')) ? strtoupper($entry['color']) : '2563EB',
            // App-ID der Person, der der Koffer gehoert
            'assignee' => AppDevice::normalizeShareCode($entry['assignee'] ?? null),
            'updated_at' => (int) ($entry['updated_at'] ?? 0),
            'deleted' => (bool) ($entry['deleted'] ?? false),
        ];
    }

    private static function cleanItem(mixed $entry): ?array
    {
        if (! is_array($entry) || ! is_string($entry['id'] ?? null) || strlen($entry['id']) > 64) {
            return null;
        }

        return [
            'id' => $entry['id'],
            'name' => mb_substr((string) ($entry['name'] ?? ''), 0, 120),
            'category' => mb_substr((string) ($entry['category'] ?? 'other'), 0, 24),
            'quantity' => max(1, min(999, (int) ($entry['quantity'] ?? 1))),
            'note' => isset($entry['note']) ? mb_substr((string) $entry['note'], 0, 300) : null,
            'suitcase_id' => isset($entry['suitcase_id']) ? mb_substr((string) $entry['suitcase_id'], 0, 64) : null,
            'packed' => (bool) ($entry['packed'] ?? false),
            'packed_at' => (int) ($entry['packed_at'] ?? 0),
            'updated_at' => (int) ($entry['updated_at'] ?? 0),
            'deleted' => (bool) ($entry['deleted'] ?? false),
        ];
    }

    /** Inhalt fuer ein Geraet (mit seiner Rolle); Koffer mit Namen der zugeordneten Person. */
    public function toApi(AppDevice $device, array $namesByCode): array
    {
        return [
            'id' => $this->id,
            'client_list_id' => $this->client_list_id,
            'name' => $this->name,
            'revision' => $this->revision,
            'my_role' => $this->roleFor($device),
            'suitcases' => array_map(fn ($s) => $s + ['assignee_code' => AppDevice::formatShareCode($s['assignee'] ?? null), 'assignee_name' => $namesByCode[$s['assignee'] ?? ''] ?? null], array_values((array) $this->suitcases)),
            'items' => array_values((array) $this->items),
        ];
    }
}
