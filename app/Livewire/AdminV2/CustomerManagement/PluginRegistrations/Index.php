<?php

namespace App\Livewire\AdminV2\CustomerManagement\PluginRegistrations;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\CustomerManagement\PluginClients\ManagesPluginList;
use App\Models\PluginEmailVerification;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Kundenverwaltung > Ausstehende Registrierungen: Plugin-Registrierungen,
 * deren E-Mail-Adresse noch bestaetigt werden muss – ansehen, loeschen und
 * alte Eintraege bereinigen.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Ausstehende Registrierungen')]
class Index extends Component
{
    use AuthorizesAdminV2, ManagesPluginList;

    /** Ab so vielen Fehlversuchen ist der Code gesperrt. */
    public const MAX_ATTEMPTS = 5;

    public const STATUSES = [
        'pending' => 'Ausstehend',
        'expired' => 'Abgelaufen',
        'verified' => 'Verifiziert',
        'exceeded' => 'Zu viele Versuche',
    ];

    public const STATUS_COLORS = [
        'pending' => 'amber',
        'expired' => 'zinc',
        'verified' => 'green',
        'exceeded' => 'red',
    ];

    #[Url(except: 'created_at')]
    public string $sort = 'created_at';

    #[Url(except: 'desc')]
    public string $direction = 'desc';

    /**
     * 'open' = alle noch nicht verifizierten (Vorgabe), 'all' = alle Eintraege,
     * sonst pending | expired | verified | exceeded
     */
    #[Url(except: 'open')]
    public string $status = 'open';

    /** '' = alle, 'today' = heute erstellt */
    #[Url(except: '')]
    public string $created = '';

    public function sortOptions(): array
    {
        return ['created_at' => 'Erstellt', 'email' => 'E-Mail', 'expires_at' => 'Läuft ab'];
    }

    protected function filterDefaults(): array
    {
        return ['status' => 'open', 'created' => ''];
    }

    /**
     * Status eines Eintrags – in dieser Reihenfolge geprueft.
     */
    public static function statusOf(PluginEmailVerification $registration): string
    {
        return match (true) {
            $registration->verified_at !== null => 'verified',
            $registration->attempts >= self::MAX_ATTEMPTS => 'exceeded',
            $registration->expires_at->isPast() => 'expired',
            default => 'pending',
        };
    }

    /**
     * Die Angaben des Formulars. Sie liegen verschluesselt in der Datenbank;
     * laesst sich ein Eintrag nicht entschluesseln (etwa nach einem Wechsel des
     * App-Schluessels oder in einer uebernommenen Datenbank), kommt null zurueck –
     * ein einzelner Eintrag soll nicht die ganze Liste unbenutzbar machen.
     *
     * @return array<string, mixed>|null
     */
    public static function formData(PluginEmailVerification $registration): ?array
    {
        try {
            return $registration->form_data ?? [];
        } catch (DecryptException) {
            return null;
        }
    }

    /**
     * Farbe der Fehlversuche: ab drei rot, darunter gelb, ohne die Farbe fuer "keine".
     */
    public static function attemptsColor(int $attempts, string $none = 'zinc'): string
    {
        return $attempts >= 3 ? 'red' : ($attempts > 0 ? 'amber' : $none);
    }

    /**
     * Anzahl der Registrierungen, die noch bestaetigt werden koennen.
     */
    #[Computed]
    public function pendingCount(): int
    {
        return PluginEmailVerification::query()
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->where('attempts', '<', self::MAX_ATTEMPTS)
            ->count();
    }

    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        $query = PluginEmailVerification::query();

        match ($this->status) {
            'open' => $query->whereNull('verified_at'),
            'pending' => $query->whereNull('verified_at')->where('expires_at', '>', now())->where('attempts', '<', self::MAX_ATTEMPTS),
            'expired' => $query->whereNull('verified_at')->where('expires_at', '<=', now()),
            'verified' => $query->whereNotNull('verified_at'),
            'exceeded' => $query->whereNull('verified_at')->where('attempts', '>=', self::MAX_ATTEMPTS),
            default => null,
        };

        if ($this->created === 'today') {
            $query->whereDate('created_at', today());
        }

        if (($term = trim($this->search)) !== '') {
            $query->where(fn ($query) => $query
                ->where('email', 'like', $this->likeTerm($term))
                ->orWhereIn('id', $this->idsMatchingFormData($term)));
        }

        $direction = $this->sortDirection();

        $query
            ->orderBy($this->sortColumn(), $direction)
            ->orderBy('id', $direction);

        return $this->paginateWithinRange($query, self::PER_PAGE);
    }

    /**
     * Die Angaben des Formulars (Firma, Ansprechpartner, Domain) liegen
     * verschluesselt in der Datenbank und lassen sich dort nicht durchsuchen –
     * deshalb hier im Speicher. Die Tabelle bleibt klein: sie enthaelt nur
     * laufende Registrierungen.
     *
     * @return array<int, int>
     */
    protected function idsMatchingFormData(string $term): array
    {
        $needle = mb_strtolower($term);

        return PluginEmailVerification::query()
            ->get(['id', 'form_data'])
            ->filter(function (PluginEmailVerification $registration) use ($needle) {
                $form = self::formData($registration) ?? [];

                foreach (['company_name', 'contact_name', 'domain'] as $field) {
                    if (str_contains(mb_strtolower((string) ($form[$field] ?? '')), $needle)) {
                        return true;
                    }
                }

                return false;
            })
            ->pluck('id')
            ->all();
    }

    public function delete(int $registrationId): void
    {
        PluginEmailVerification::findOrFail($registrationId)->delete();

        $this->selected = array_values(array_diff($this->selected, [(string) $registrationId]));
        unset($this->rows, $this->pendingCount);

        $this->dispatch('adminv2-toast', message: 'Eintrag wurde gelöscht.');
    }

    protected function deleteRecords(array $ids): int
    {
        unset($this->pendingCount);

        return PluginEmailVerification::whereIn('id', $ids)->delete();
    }

    /**
     * Abgelaufene und bereits verifizierte Eintraege loeschen, die aelter als 24 Stunden sind.
     */
    public function cleanup(): void
    {
        $deleted = PluginEmailVerification::query()
            ->where(fn ($query) => $query
                ->where('expires_at', '<', now()->subDay())
                ->orWhere(fn ($query) => $query->whereNotNull('verified_at')->where('verified_at', '<', now()->subDay())))
            ->delete();

        $this->selected = [];
        unset($this->rows, $this->pendingCount);

        $this->dispatch('adminv2-toast', message: 'Bereinigung abgeschlossen: '.($deleted === 1 ? '1 Eintrag wurde' : $deleted.' Einträge wurden').' gelöscht.');
    }

    public function render()
    {
        return view('livewire.admin-v2.customer-management.plugin-registrations.index');
    }
}
