<?php

namespace App\Livewire\AdminV2\Concerns;

use App\Jobs\AskAiQuestionJob;
use App\Models\AiFieldPrompt;
use App\Models\AiQuestion;
use App\Services\AiCheckService;
use App\Support\AdminV2\AiAreas;
use App\Support\AdminV2\AiModelOptions;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;

/**
 * KI-Feldrecherche: an einzelnen Feldern eines Formulars die KI (ChatGPT,
 * Claude oder Gemini) nach genau diesem Wert fragen – im Hintergrund (Queue).
 *
 * - Prompts mit Platzhaltern ({name}, {iso_code}, …) lassen sich testen und als
 *   Vorlage des Feldes speichern; die Standardvorlage ist bei jedem Eintrag
 *   vorausgewaehlt und wird mit dessen Werten gefuellt.
 * - Die KI liefert Wert und Begruendung; "Uebernehmen" schreibt beides ins
 *   Formular (gespeichert wird erst mit "Speichern").
 * - Jede Anfrage bleibt als Protokoll (AiQuestion): Vorlage, gesendeter Prompt,
 *   Vorschlag, Quellen, Kosten – und wer was wann uebernommen und gespeichert hat.
 *
 * Setzt RunsAiChecks voraus (aiArea, aiContext, aiApply, aiApplyNote, aiSubject).
 */
trait ResearchesAiFields
{
    /**
     * Eingaben je Feld: Vorlage, Prompt, KI, Modell und – zum Speichern als
     * Vorlage – Name und Standard.
     *
     * @var array<string, array{template: string, prompt: string, provider: string, model: string, name: string, default: bool}>
     */
    public array $aiResearch = [];

    /** @var array<int, int> beim letzten Abfragen noch offene Anfragen */
    public array $aiResearchOpenIds = [];

    /** Feld, dessen Verlauf angezeigt wird; "*" = alle Felder des Eintrags */
    public string $aiResearchHistoryKey = '';

    /**
     * Die Felder mit KI-Recherche: Schluessel => Bezeichnung, Abschnitt, erlaubte
     * Werte (null = Fliesstext) und ob die Begruendung in eine Notiz kann.
     *
     * @return array<string, array{label: string, section: string, format: ?string, note: bool}>
     */
    abstract protected function aiResearchFields(): array;

    /**
     * Ein Vorschlag der KI so, wie er im Feld stuende (z. B. "3 – Mittel");
     * null, wenn er sich nicht uebernehmen laesst.
     */
    abstract protected function aiResearchDisplay(string $key, string $value): ?string;

    /**
     * Platzhalter zum Einfuegen: Schluessel => Bezeichnung.
     *
     * @return array<string, string>
     */
    abstract protected function aiResearchPlaceholders(): array;

    public function mountResearchesAiFields(): void
    {
        $this->aiResearchOpenIds = $this->aiResearchQuery()->open()->pluck('id')->all();
    }

    /**
     * Das Feld zum ersten Mal aufklappen: Standardvorlage laden, sonst einen
     * Vorschlag fuer den Prompt.
     */
    public function openAiResearch(string $key): void
    {
        if (! isset($this->aiResearchFields()[$key]) || isset($this->aiResearch[$key])) {
            return;
        }

        $template = $this->aiResearchTemplates->get($key)?->firstWhere('is_default', true);
        $provider = (string) array_key_first(AiModelOptions::providers());

        $this->aiResearch[$key] = ['template' => '', 'prompt' => $this->aiResearchStarterPrompt($key), 'provider' => $provider, 'model' => AiModelOptions::defaultModel($provider), 'name' => '', 'default' => false];

        if ($template) {
            $this->loadAiResearchTemplate($key, $template);
        }
    }

    /**
     * Vorlage oder KI gewechselt: Prompt bzw. Modell nachziehen.
     */
    public function updatedAiResearch(mixed $value, string $path): void
    {
        [$key, $property] = array_pad(explode('.', $path, 2), 2, null);

        if (! isset($this->aiResearch[$key])) {
            return;
        }

        if ($property === 'provider') {
            $this->aiResearch[$key]['model'] = AiModelOptions::defaultModel((string) $value);
        }

        if ($property === 'template') {
            $template = ctype_digit((string) $value) ? $this->aiResearchTemplates->get($key)?->firstWhere('id', (int) $value) : null;

            $template ? $this->loadAiResearchTemplate($key, $template) : $this->aiResearch[$key]['default'] = false;
        }

        $this->resetValidation('aiResearch.'.$key.'.*');
    }

    /**
     * Den Prompt mit den Werten dieses Eintrags an die KI senden.
     */
    public function sendAiResearch(string $key): void
    {
        $field = $this->aiResearchFields()[$key] ?? null;
        $subject = $this->aiSubject();

        if (! $field || ! $subject || ! isset($this->aiResearch[$key])) {
            return;
        }

        $this->validateAiResearch($key);
        $state = $this->aiResearch[$key];

        $run = AiQuestion::create([
            'user_id' => auth('web')->id(),
            'area' => $this->aiArea(),
            'section' => $field['section'],
            'field' => $key,
            'ai_field_prompt_id' => ctype_digit($state['template']) ? (int) $state['template'] : null,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'title' => $this->aiReviewTitle(),
            'provider' => $state['provider'],
            'model' => $state['model'],
            'question' => trim($state['prompt']),
            'prompt' => $this->aiResearchPrompt($key, $state['prompt']),
            'status' => AiQuestion::STATUS_QUEUED,
        ]);

        $this->aiResearchOpenIds[] = $run->id;
        unset($this->aiResearchRuns);

        AskAiQuestionJob::dispatch($run->id);

        // Mit der Queue "sync" (Tests, lokal) ist die Antwort schon da.
        $this->checkAiResearch();
    }

    /**
     * Den Prompt als Vorlage dieses Feldes speichern – als neue Vorlage oder in
     * die gewaehlte. Gilt danach fuer alle Eintraege des Bereichs.
     */
    public function saveAiResearchTemplate(string $key, bool $asNew = false): void
    {
        if (! isset($this->aiResearchFields()[$key], $this->aiResearch[$key])) {
            return;
        }

        $current = $asNew ? null : $this->aiResearchTemplates->get($key)?->firstWhere('id', (int) $this->aiResearch[$key]['template']);

        if (! $current) {
            $this->aiResearch[$key]['name'] = trim($this->aiResearch[$key]['name']);
        }

        $this->validateAiResearch($key, withName: ! $current);
        $state = $this->aiResearch[$key];

        $attributes = ['prompt' => trim($state['prompt']), 'provider' => $state['provider'], 'model' => $state['model'], 'updated_by' => auth('web')->id()];
        // Die erste Vorlage eines Feldes wird dessen Standard.
        $isFirst = AiFieldPrompt::query()->where('area', $this->aiArea())->where('field', $key)->doesntExist();

        $template = $current
            ? tap($current)->update($attributes)
            : AiFieldPrompt::create($attributes + ['area' => $this->aiArea(), 'field' => $key, 'name' => $state['name'], 'created_by' => auth('web')->id()]);

        if ($state['default'] || $isFirst) {
            $template->makeDefault();
        }

        $this->aiResearch[$key]['template'] = (string) $template->id;
        $this->aiResearch[$key]['name'] = '';
        $this->aiResearch[$key]['default'] = (bool) $template->fresh()->is_default;
        unset($this->aiResearchTemplates);

        $this->dispatch('adminv2-toast', message: 'Vorlage „'.$template->name.'“ gespeichert – sie steht bei jedem Eintrag an diesem Feld bereit.');
    }

    public function deleteAiResearchTemplate(string $key): void
    {
        $template = $this->aiResearchTemplates->get($key)?->firstWhere('id', (int) ($this->aiResearch[$key]['template'] ?? 0));

        if (! $template) {
            return;
        }

        $template->delete();
        $this->aiResearch[$key]['template'] = '';
        $this->aiResearch[$key]['default'] = false;
        unset($this->aiResearchTemplates);

        $this->dispatch('adminv2-toast', message: 'Vorlage „'.$template->name.'“ gelöscht. Frühere Anfragen behalten ihren Prompt im Verlauf.');
    }

    /**
     * Den Vorschlag der KI ins Formular uebernehmen: den Wert ins Feld, die
     * Begruendung in die Notiz. Festgehalten wird, was vorher im Feld stand.
     */
    public function applyAiResearch(int $id): void
    {
        $run = $this->aiResearchQuery()->where('status', AiQuestion::STATUS_DONE)->find($id);
        $field = $run ? ($this->aiResearchFields()[$run->field] ?? null) : null;

        if (! $run || ! $field || ($run->value === null && $run->note === null)) {
            return;
        }

        $before = $this->aiResearchCurrentValue($run->field);

        if ($run->value !== null && ($this->aiResearchDisplay($run->field, $run->value) === null || ! $this->aiApply($run->field, $run->value))) {
            $this->dispatch('adminv2-toast', message: 'Dieser Vorschlag passt nicht ins Feld – bitte von Hand eintragen.', variant: 'danger');

            return;
        }

        if ($field['note'] && $run->note !== null) {
            $this->aiApplyNote($run->field, $run->note);
        }

        $run->update([
            'previous_value' => $before,
            'applied_value' => $this->aiResearchCurrentValue($run->field),
            'applied_at' => now(),
            'applied_by' => auth('web')->id(),
            'saved_at' => null,
        ]);
        unset($this->aiResearchRuns, $this->aiResearchHistory);

        $this->dispatch('adminv2-toast', message: 'KI-Vorschlag für „'.$field['label'].'“ übernommen – noch nicht gespeichert.');
    }

    /**
     * Wird abgefragt, solange Anfragen offen sind: fertige melden sich.
     */
    public function checkAiResearch(): void
    {
        unset($this->aiResearchRuns, $this->aiResearchHistory);

        $finished = $this->aiResearchQuery()->whereKey($this->aiResearchOpenIds)->get()->reject->isOpen();

        foreach ($finished as $run) {
            $label = $this->aiResearchFields()[$run->field]['label'] ?? $run->field;
            $this->dispatch('adminv2-toast',
                message: 'KI-Recherche „'.$label.'“ '.($run->failureMessage() ? 'fehlgeschlagen.' : 'fertig – Vorschlag steht am Feld.'),
                variant: $run->failureMessage() ? 'danger' : 'success',
            );
        }

        $this->aiResearchOpenIds = array_values(array_diff($this->aiResearchOpenIds, $finished->pluck('id')->all()));
    }

    /**
     * Verlauf eines Feldes ("*" = aller Felder) anzeigen.
     */
    public function showAiResearchHistory(string $key): void
    {
        if ($key !== '*' && ! isset($this->aiResearchFields()[$key])) {
            return;
        }

        $this->aiResearchHistoryKey = $key;
        unset($this->aiResearchHistory);

        $this->modal('ai-research-history')->show();
    }

    /**
     * Nach dem Speichern: uebernommene Vorschlaege gelten als gespeichert.
     */
    protected function markAiResearchSaved(): void
    {
        $this->aiResearchQuery()
            ->where('applied_by', auth('web')->id())
            ->whereNotNull('applied_at')
            ->whereNull('saved_at')
            ->update(['saved_at' => now()]);

        unset($this->aiResearchRuns, $this->aiResearchHistory);
    }

    /**
     * Gespeicherte Vorlagen des Bereichs, je Feld.
     *
     * @return Collection<string, Collection<int, AiFieldPrompt>>
     */
    #[Computed]
    public function aiResearchTemplates(): Collection
    {
        return AiFieldPrompt::query()
            ->where('area', $this->aiArea())
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get()
            ->groupBy('field');
    }

    /**
     * Die juengsten Anfragen je Feld dieses Eintrags (aller Benutzer), neueste zuerst.
     *
     * @return Collection<string, Collection<int, AiQuestion>>
     */
    #[Computed]
    public function aiResearchRuns(): Collection
    {
        return $this->aiResearchQuery()
            ->with('appliedBy:id,name')
            ->latest('id')
            ->limit(300)
            ->get()
            ->groupBy('field');
    }

    /**
     * Der Verlauf fuer das Fenster "KI-Verlauf".
     *
     * @return Collection<int, AiQuestion>
     */
    #[Computed]
    public function aiResearchHistory(): Collection
    {
        if ($this->aiResearchHistoryKey === '') {
            return collect();
        }

        return $this->aiResearchQuery()
            ->when($this->aiResearchHistoryKey !== '*', fn ($query) => $query->where('field', $this->aiResearchHistoryKey))
            ->with(['user:id,name', 'appliedBy:id,name', 'template:id,name'])
            ->latest('id')
            ->get();
    }

    /**
     * Der Prompt mit den Werten dieses Eintrags – so, wie ihn die KI erhaelt
     * (ohne die angehaengte Formatvorgabe).
     */
    public function aiResearchPreview(string $key): string
    {
        return $this->aiResolvePlaceholders((string) ($this->aiResearch[$key]['prompt'] ?? ''));
    }

    /**
     * Platzhalter im Prompt, die es nicht gibt (Tippfehler).
     *
     * @return array<int, string>
     */
    public function aiResearchUnknownPlaceholders(string $key): array
    {
        preg_match_all('/\{([a-z0-9_]+)\}/', (string) ($this->aiResearch[$key]['prompt'] ?? ''), $matches);

        return array_values(array_unique(array_diff($matches[1], array_keys($this->aiContext()))));
    }

    /**
     * Der vollstaendige Prompt: der eigene Text mit aufgeloesten Platzhaltern,
     * dazu, welcher Wert in welchem Format gesucht ist.
     */
    protected function aiResearchPrompt(string $key, string $template): string
    {
        $field = $this->aiResearchFields()[$key];
        $entry = AiAreas::label($this->aiArea()).' › „'.$this->aiReviewTitle().'“';
        $format = $field['format'] ?? 'Fließtext auf Deutsch für Reisende – in eigenen Worten, sachlich, ohne Quellenverweise im Text';
        $json = $field['note']
            ? '{"value": "<Wert im erlaubten Format oder null>", "note": "<Begründung für Reisende auf Deutsch, 1–3 Sätze>"}'
            : '{"value": "<Text oder null>"}';

        return trim($this->aiResolvePlaceholders($template))."\n\n---\n"
            .'Gesucht ist der Wert für das Feld „'.$field['label'].'“ im Eintrag '.$entry.".\n"
            .'Erlaubter Wert: '.$format.".\n"
            ."Recherchiere im Internet und bevorzuge amtliche Quellen (z. B. Auswärtiges Amt). Ist der Wert nicht verlässlich feststellbar, setze \"value\" auf null.\n"
            ."Antworte ausschließlich mit einem JSON-Objekt ohne weiteren Text:\n".$json;
    }

    /**
     * {schluessel} durch den aktuellen Wert des Formulars ersetzen; Unbekanntes bleibt stehen.
     */
    protected function aiResolvePlaceholders(string $text): string
    {
        $context = $this->aiContext();
        $service = app(AiCheckService::class);

        return (string) preg_replace_callback('/\{([a-z0-9_]+)\}/', fn (array $match) => array_key_exists($match[1], $context) ? $service->format($context[$match[1]]) : $match[0], $text);
    }

    /**
     * Der aktuelle Wert eines Feldes, lesbar – fuer "vorher/nachher" im Verlauf.
     */
    protected function aiResearchCurrentValue(string $key): ?string
    {
        $value = $this->aiContext()[$key] ?? null;

        return $value === null || $value === '' ? null : app(AiCheckService::class)->format($value);
    }

    protected function aiResearchStarterPrompt(string $key): string
    {
        return 'Wie ist „'.$this->aiResearchFields()[$key]['label'].'“ für {name} einzuschätzen? Berücksichtige die aktuellen Reise- und Sicherheitshinweise des Auswärtigen Amts und weitere seriöse Quellen.';
    }

    protected function loadAiResearchTemplate(string $key, AiFieldPrompt $template): void
    {
        $providers = AiModelOptions::providers();
        $provider = isset($providers[$template->provider]) ? $template->provider : (string) array_key_first($providers);

        $this->aiResearch[$key] = array_merge($this->aiResearch[$key], [
            'template' => (string) $template->id,
            'prompt' => $template->prompt,
            'provider' => $provider,
            'model' => $provider === $template->provider && isset(AiModelOptions::models($provider)[$template->model]) ? $template->model : AiModelOptions::defaultModel($provider),
            'default' => $template->is_default,
        ]);
    }

    protected function validateAiResearch(string $key, bool $withName = false): void
    {
        $base = 'aiResearch.'.$key.'.';
        $provider = (string) ($this->aiResearch[$key]['provider'] ?? '');

        $this->validate([
            $base.'prompt' => ['required', 'string', 'min:10', 'max:6000'],
            $base.'provider' => ['required', Rule::in(array_keys(AiModelOptions::providers()))],
            $base.'model' => ['required', 'string', Rule::in(array_keys(AiModelOptions::models($provider)))],
            $base.'name' => [Rule::requiredIf($withName), 'nullable', 'string', 'max:100'],
        ], [
            $base.'prompt.required' => 'Bitte einen Prompt eingeben.',
            $base.'prompt.min' => 'Der Prompt ist zu kurz.',
            $base.'prompt.max' => 'Der Prompt ist zu lang (höchstens 6000 Zeichen).',
            $base.'provider.required' => 'Bitte eine KI wählen.',
            $base.'provider.in' => 'Für diese KI ist kein API-Schlüssel hinterlegt (System > KI).',
            $base.'model.required' => 'Bitte ein Modell wählen.',
            $base.'model.in' => 'Dieses Modell steht für die gewählte KI nicht zur Auswahl.',
            $base.'name.required' => 'Bitte einen Namen für die Vorlage eingeben.',
        ]);
    }

    /**
     * Feldrecherchen dieses Eintrags (aller Benutzer – das Protokoll gehoert zum Eintrag).
     *
     * @return \Illuminate\Database\Eloquent\Builder<AiQuestion>
     */
    protected function aiResearchQuery()
    {
        $subject = $this->aiSubject();

        return AiQuestion::query()
            ->where('area', $this->aiArea())
            ->whereNotNull('field')
            ->where('subject_type', $subject?->getMorphClass())
            ->where('subject_id', $subject?->getKey());
    }
}
