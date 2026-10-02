<?php

namespace App\Livewire\AdminV2\Events;

use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Livewire\AdminV2\Concerns\HandlesAiSuggestions;
use App\Livewire\AdminV2\Concerns\StartsAiEventSearch;
use App\Models\AiEventSearch;
use App\Models\AiEventSuggestion;
use App\Models\Country;
use App\Models\EventType;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Ereignisse > KI Suchergebnisse: die Laeufe der KI-Suche und alles, was sie
 * gefunden hat – offen, als Entwurf angelegt oder verworfen.
 */
#[Layout('components.layouts.adminv2.app')]
#[Title('KI Suchergebnisse')]
class AiResults extends Component
{
    use AuthorizesAdminV2;
    use HandlesAiSuggestions;
    use StartsAiEventSearch;
    use WithPagination;

    /** new | converted | dismissed | all */
    #[Url(except: 'new')]
    public string $status = 'new';

    #[Url(except: '')]
    public string $search = '';

    /** ID eines Suchlaufs: nur dessen Ergebnisse zeigen. */
    #[Url(as: 'run', except: '')]
    public string $searchId = '';

    private const PER_PAGE = 12;

    private const RUNS_PER_PAGE = 8;

    /**
     * @return array<string, string>
     */
    public function tabs(): array
    {
        return [
            AiEventSuggestion::STATUS_NEW => 'Offen',
            AiEventSuggestion::STATUS_CONVERTED => 'Als Entwurf angelegt',
            AiEventSuggestion::STATUS_DISMISSED => 'Verworfen',
            'all' => 'Alle',
        ];
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['status', 'search', 'searchId'], true)) {
            $this->resetPage();
        }
    }

    /**
     * Die Ergebnisse eines Suchlaufs zeigen bzw. die Auswahl wieder aufheben.
     */
    public function showRun(int $searchId): void
    {
        $this->searchId = $this->searchId === (string) $searchId ? '' : (string) $searchId;
        // Ein Lauf kann auch bereits Erledigtes gefunden haben.
        $this->status = $this->searchId !== '' ? 'all' : AiEventSuggestion::STATUS_NEW;
        $this->resetPage();
    }

    protected function forgetAiSuggestions(): void
    {
        unset($this->tabCounts);
    }

    /**
     * Wird waehrend einer laufenden Suche in kurzen Abstaenden aufgerufen.
     */
    public function refreshAiSearch(): void
    {
        unset($this->latestAiSearch, $this->tabCounts);
    }

    protected function baseQuery(): Builder
    {
        $query = AiEventSuggestion::query();

        if (($runId = (int) $this->searchId) > 0) {
            $query->where('search_id', $runId);
        }

        if (($search = trim($this->search)) !== '') {
            $like = '%'.$search.'%';

            $query->where(fn (Builder $q) => $q
                ->where('title', 'like', $like)
                ->orWhere('summary', 'like', $like)
                ->orWhere('location', 'like', $like));
        }

        return $query;
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function tabCounts(): array
    {
        $counts = $this->baseQuery()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return collect(array_keys($this->tabs()))
            ->mapWithKeys(fn (string $tab) => [$tab => $tab === 'all' ? (int) $counts->sum() : (int) ($counts[$tab] ?? 0)])
            ->all();
    }

    #[Computed]
    public function eventTypes()
    {
        return EventType::query()->get(['code', 'name', 'icon']);
    }

    protected function suggestions()
    {
        return $this->baseQuery()
            ->when($this->status !== 'all', fn (Builder $query) => $query->where('status', $this->status))
            ->with(['search.profile', 'customEvent'])
            ->latest('id')
            ->paginate(self::PER_PAGE);
    }

    protected function runs()
    {
        return AiEventSearch::query()
            ->with(['starter', 'profile'])
            ->withCount('suggestions')
            ->latest('id')
            ->paginate(self::RUNS_PER_PAGE, pageName: 'runs');
    }

    public function render()
    {
        $suggestions = $this->suggestions();

        return view('livewire.admin-v2.events.ai-results', [
            'suggestions' => $suggestions,
            'runs' => $this->runs(),
            'countryNames' => Country::query()
                ->whereIn('iso_code', $suggestions->getCollection()->flatMap(fn (AiEventSuggestion $suggestion) => $suggestion->country_codes ?? [])->unique())
                ->get()
                ->mapWithKeys(fn (Country $country) => [strtoupper((string) $country->iso_code) => $country->getName('de')]),
        ]);
    }
}
