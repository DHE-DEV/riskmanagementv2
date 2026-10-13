<?php

namespace App\Livewire\AdminV2\Tasks;

use App\Jobs\FillMissingRegionInfoJob;
use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Models\AdminTask;
use App\Models\Country;
use App\Models\RegionInfoRun;
use App\Support\AdminV2\RegionInfo;
use App\Support\AdminV2\RegionInfoFill;
use App\Support\AdminV2\RegionInfoTask;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Kasten "KI-Lauf" an der Aufgabe "Regionsinfos per KI ergänzen – <Land>":
 * zeigt, was fehlt, startet regions:fill-missing fuer das Land in der Queue
 * und zeigt den Stand des Laufs (mit Anhalten). Am Ende erledigt der Lauf die
 * Aufgabe und verschickt die Mail – wie beim Befehl.
 */
class RegionInfoRunPanel extends Component
{
    use AuthorizesAdminV2;

    #[Locked]
    public int $taskId;

    public bool $web = true;

    public bool $discover = true;

    /** E-Mail-Adressen fuer die Meldung am Ende, mit Komma getrennt. */
    public string $notify = '';

    /** Zuletzt gesehener Lauf und Status ("ID:Status") – aendert er sich, laedt die Aufgabenseite neu. */
    #[Locked]
    public ?string $runState = null;

    public function mount(int $taskId): void
    {
        $this->taskId = $taskId;
        $this->notify = (string) auth('web')->user()?->email;
        $this->runState = $this->run ? $this->run->id.':'.$this->run->status : null;
    }

    #[Computed]
    public function task(): AdminTask
    {
        return AdminTask::findOrFail($this->taskId);
    }

    #[Computed]
    public function country(): ?Country
    {
        return RegionInfoTask::countryOf($this->task);
    }

    /**
     * Der Lauf dieser Aufgabe – laufend oder zuletzt beendet; laeuft fuer das
     * Land einer ohne Aufgabe (Sammellauf per Befehl), dann dieser.
     */
    #[Computed]
    public function run(): ?RegionInfoRun
    {
        $run = RegionInfoTask::lastRun($this->task);

        if ($run?->isRunning() || ! $this->country) {
            return $run;
        }

        return RegionInfoFill::runningFor($this->country) ?? $run;
    }

    /**
     * Was fehlt – nur berechnet, solange das Startformular zu sehen ist.
     *
     * @return array{regions: int, subregions: int, discover: int, gaps: int}|null
     */
    #[Computed]
    public function preview(): ?array
    {
        if (! $this->country) {
            return null;
        }

        $plan = RegionInfoFill::plan(collect([$this->country]), discover: $this->discover);

        return [
            'regions' => $plan['regions']->count(),
            'subregions' => $plan['subregions']->count(),
            'discover' => $plan['discover']->count(),
            'gaps' => $plan['regions']->merge($plan['subregions'])->sum(fn (array $item) => RegionInfo::missingCount($item['missing'])),
        ];
    }

    #[Computed]
    public function failures(): Collection
    {
        return $this->run ? RegionInfoFill::failures($this->run) : collect();
    }

    public function start(): void
    {
        $notify = array_values(array_filter(array_map('trim', explode(',', $this->notify))));

        $this->validate(
            ['notify' => ['nullable', 'string', 'max:1000', function ($attribute, $value, $fail) use ($notify) {
                if ($invalid = array_filter($notify, fn ($address) => ! filter_var($address, FILTER_VALIDATE_EMAIL))) {
                    $fail('Ungültige E-Mail-Adresse: '.implode(', ', $invalid));
                }
            }]],
        );

        $country = $this->country;

        if (! $country) {
            return;
        }

        if ($running = RegionInfoFill::runningFor($country)) {
            $this->dispatch('adminv2-toast', message: "Für {$country->getName('de')} läuft schon Lauf #{$running->id}.");
            $this->reload();

            return;
        }

        $plan = RegionInfoFill::plan(collect([$country]), discover: $this->discover);
        $task = $this->task;

        if ($plan['regions']->isEmpty() && $plan['subregions']->isEmpty() && $plan['discover']->isEmpty()) {
            RegionInfoTask::nothingToDo($task);
            $this->dispatch('adminv2-toast', message: 'Nichts zu tun – keine Lücken. Die Aufgabe ist erledigt.');
            $this->reload();

            return;
        }

        $run = FillMissingRegionInfoJob::start(
            $plan['regions']->keys()->all(),
            $plan['subregions']->keys()->all(),
            [$country->id => $country->getName('de')],
            $this->web,
            $notify,
            $task->id,
            $plan['discover']->keys()->all(),
        );

        RegionInfoTask::started($task, $run);
        FillMissingRegionInfoJob::dispatch($run->id);

        $this->dispatch('adminv2-toast', message: "Lauf #{$run->id} gestartet – er läuft im Hintergrund, die Seite muss nicht offen bleiben.");
        $this->reload();
    }

    public function cancel(): void
    {
        if ($this->run?->isRunning() && RegionInfoFill::cancel($this->run->id)) {
            $this->dispatch('adminv2-toast', message: 'Lauf angehalten – was gerade in Arbeit ist, wird noch fertig.');
        }

        $this->reload();
    }

    /**
     * Neu laden (Abfrage alle paar Sekunden, solange der Lauf laeuft). Hat
     * sich der Stand des Laufs geaendert, aktualisiert die Aufgabenseite
     * Status und Verlauf mit.
     */
    public function reload(): void
    {
        unset($this->task, $this->run, $this->preview, $this->failures);

        $state = $this->run ? $this->run->id.':'.$this->run->status : null;

        if ($state !== $this->runState) {
            $this->runState = $state;
            $this->dispatch('adminv2-task-changed');
        }
    }

    public function render()
    {
        return view('livewire.admin-v2.tasks.region-info-run-panel');
    }
}
