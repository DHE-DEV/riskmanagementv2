<?php

namespace App\Livewire\AdminV2\Events;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\QueriesLists;
use App\Models\InfosystemEntry;
use App\Services\PassolutionApiService;
use App\Support\AdminV2\Infosystem as InfosystemSupport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Eintraege des Passolution Infosystems: Sie werden aus der Passolution-API
 * abgerufen, in der Datenbank abgelegt und koennen von hier aus als
 * Ereignis angelegt werden.
 *
 * Zwei Abrufe wie im bisherigen Admin: "Synchronisieren" holt die erste
 * Seite (die aktuellsten Eintraege), "Letzte 100 abrufen" blaettert, bis 100
 * Eintraege gespeichert sind.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('Passolution Infosystem')]
class Infosystem extends Component
{
    use AuthorizesAdminV2, QueriesLists, WithPagination;

    public const PER_PAGE = 25;

    /** Sprache und Umfang der Abrufe – wie im bisherigen Admin. */
    public const SYNC_LANG = 'de';

    public const SYNC_LIMIT = 100;

    #[Url(except: '')]
    public string $search = '';

    /** '' = alle, yes = veroeffentlicht, no = nicht veroeffentlicht */
    #[Url(except: '')]
    public string $published = '';

    #[Url(except: '')]
    public string $lang = '';

    /** '' = alle, yes = aktiv, no = inaktiv */
    #[Url(except: '')]
    public string $active = '';

    /** '' = alle, yes = archiviert, no = nicht archiviert */
    #[Url(except: '')]
    public string $archive = '';

    #[Url(except: 'tagdate')]
    public string $sort = 'tagdate';

    #[Url(except: 'desc')]
    public string $direction = 'desc';

    private const FILTERS = ['search', 'published', 'lang', 'active', 'archive'];

    /**
     * @return array<string, string>
     */
    public function sortOptions(): array
    {
        return [
            'tagdate' => 'Datum',
            'api_id' => 'API-ID',
            'country_code' => 'Land',
            'published_at' => 'Veröffentlicht am',
            'created_at' => 'Abgerufen',
        ];
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['sort', ...self::FILTERS], true)) {
            $this->resetPage();
        }
    }

    public function toggleDirection(): void
    {
        $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(self::FILTERS);
        $this->resetPage();
    }

    public function hasFilters(): bool
    {
        foreach (self::FILTERS as $filter) {
            if ($this->{$filter} !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Ist der Zugang zur Passolution-API eingerichtet (PASSOLUTION_API_KEY)?
     */
    #[Computed]
    public function hasCredentials(): bool
    {
        return app(PassolutionApiService::class)->hasValidCredentials();
    }

    #[Computed]
    public function rows(): LengthAwarePaginator
    {
        $query = InfosystemEntry::query()->with('publishedEvent');

        if (($term = trim($this->search)) !== '') {
            $this->whereEveryWord($query, $term, ['header', 'content', 'country_code', 'api_id']);
        }

        // Aeltere Eintraege haben in "is_published" noch NULL – sie gelten als nicht veroeffentlicht.
        match ($this->published) {
            'yes' => $query->where('is_published', true),
            'no' => $query->where(fn ($q) => $q->where('is_published', false)->orWhereNull('is_published')),
            default => null,
        };

        if (isset(InfosystemSupport::LANGUAGES[$this->lang])) {
            $query->where('lang', $this->lang);
        }

        match ($this->active) {
            'yes' => $query->where('active', true),
            'no' => $query->where('active', false),
            default => null,
        };

        match ($this->archive) {
            'yes' => $query->where('archive', true),
            'no' => $query->where('archive', false),
            default => null,
        };

        $column = isset($this->sortOptions()[$this->sort]) ? $this->sort : 'tagdate';
        $direction = $this->direction === 'asc' ? 'asc' : 'desc';

        if ($column === 'published_at') {
            // Nicht veroeffentlichte Eintraege stehen immer hinten.
            $query->orderByRaw('published_at is null asc');
        }

        $query->orderBy($column, $direction)->orderBy('id', $direction);

        return $this->paginateWithinRange($query, self::PER_PAGE);
    }

    /**
     * Die aktuellen Eintraege abrufen – die erste Seite der API.
     */
    public function sync(): void
    {
        $this->modal('infosystem-sync')->close();

        if (! $this->ensureCredentials()) {
            return;
        }

        try {
            $result = app(PassolutionApiService::class)->fetchAndStore(self::SYNC_LANG, 1);
        } catch (\Throwable $e) {
            $this->failed('Ein unerwarteter Fehler ist aufgetreten: '.$e->getMessage());

            return;
        }

        if (! ($result['success'] ?? false)) {
            $this->failed($result['error'] ?? 'Die Verbindung zum externen Infosystem konnte nicht hergestellt werden.');

            return;
        }

        $this->finished((int) $result['stored'], 'Daten erfolgreich synchronisiert: '.$this->countLabel((int) $result['stored']).' aus dem externen Infosystem abgerufen und gespeichert.');
    }

    /**
     * Die letzten 100 Eintraege abrufen – ueber mehrere Seiten der API.
     */
    public function syncLast100(): void
    {
        $this->modal('infosystem-sync-100')->close();

        if (! $this->ensureCredentials()) {
            return;
        }

        try {
            $result = app(PassolutionApiService::class)->fetchAndStoreMultiple(self::SYNC_LANG, self::SYNC_LIMIT);
        } catch (\Throwable $e) {
            $this->failed('Ein unerwarteter Fehler ist aufgetreten: '.$e->getMessage());

            return;
        }

        if (! ($result['success'] ?? false)) {
            $message = 'Fehler beim Abrufen der '.self::SYNC_LIMIT.' Einträge.';

            if (! empty($result['errors'])) {
                $message .= ' Details: '.implode(', ', $result['errors']);
            }

            $this->failed($message);

            return;
        }

        $pages = (int) ($result['pages_fetched'] ?? 1);

        $this->finished((int) $result['stored'], 'Erfolgreich '.$this->countLabel((int) $result['stored']).' über '.$pages.' '.($pages === 1 ? 'Seite' : 'Seiten').' abgerufen und gespeichert.');
    }

    protected function ensureCredentials(): bool
    {
        if ($this->hasCredentials) {
            return true;
        }

        $this->failed('API-Konfiguration fehlt: Bitte PASSOLUTION_API_KEY in der .env-Datei setzen.');

        return false;
    }

    protected function finished(int $stored, string $message): void
    {
        unset($this->rows);
        $this->resetPage();

        $this->dispatch('adminv2-toast', message: $message);
    }

    protected function failed(string $message): void
    {
        $this->dispatch('adminv2-toast', message: $message, variant: 'danger');
    }

    protected function countLabel(int $count): string
    {
        return $count === 1 ? '1 Eintrag' : number_format($count, 0, ',', '.').' Einträge';
    }

    /**
     * Adresse, unter der aus dem Eintrag ein neues Ereignis entsteht.
     */
    public function createEventUrl(InfosystemEntry $entry): string
    {
        return route('adminv2.events.create', ['infosystem' => $entry->api_id]);
    }

    public function render()
    {
        return view('livewire.admin-v2.events.infosystem');
    }
}
