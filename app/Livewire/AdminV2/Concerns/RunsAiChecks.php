<?php

namespace App\Livewire\AdminV2\Concerns;

use App\Jobs\ReviewAiFieldsJob;
use App\Models\AiCheck;
use App\Models\AiFieldReview;
use App\Services\AiCheckService;
use App\Services\AiCheckTaskService;
use App\Services\AiFieldReviewService;
use App\Services\OpenAiModelService;
use App\Support\AdminV2\AiAreas;
use App\Support\AiSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Throwable;

/**
 * KI-Pruefungen an den Abschnitten eines Stammdaten-Formulars: die
 * Schaltflaeche eines Abschnitts oeffnet das Fenster mit den dort hinterlegten
 * Pruefungen; ausgefuehrt wird mit den aktuellen Werten des Abschnitts.
 */
trait RunsAiChecks
{
    /** Abschnitt, fuer den das Fenster offen ist; '' = geschlossen */
    public string $aiSection = '';

    /** ID einer hinterlegten Pruefung oder "custom" fuer einen eigenen Prompt */
    public string $aiCheckId = '';

    public string $aiCustomPrompt = '';

    /**
     * Prompt der gewaehlten hinterlegten Pruefung fuer diesen Lauf. Er laesst
     * sich vor dem Ausfuehren anpassen – die hinterlegte Pruefung bleibt, wie sie ist.
     */
    public string $aiPromptDraft = '';

    /** Modell fuer den eigenen Prompt; leer = Standardmodell */
    public string $aiCustomModel = '';

    /** Eigenen Prompt nach dem Lauf als Pruefung dieses Abschnitts speichern */
    public bool $aiSaveAsCheck = false;

    public string $aiCheckName = '';

    /**
     * Ergebnis der automatischen Feldpruefung – bleibt nach dem Schliessen des
     * Fensters unter den Feldern stehen, bis es verworfen wird.
     *
     * @var array{section: string, fields: array<string, array{status: string, value: ?string, note: ?string, sources?: array<int, string>, applied?: bool, note_applied?: bool}>, summary: ?string, sources?: array<int, array{url: string, title: ?string}>, usage: ?array}|null
     */
    public ?array $aiReview = null;

    /** Auftrag der Feldpruefung, dessen Ergebnis gerade im Formular steht */
    public ?int $aiReviewId = null;

    /** Auftrag, der beim Laden der Seite geoeffnet wird (?ki-pruefung=…) */
    public ?int $aiReviewToOpen = null;

    /** @var array<int, int> fertige Auftraege, die schon gemeldet, aber noch nicht geoeffnet sind */
    public array $aiAnnouncedReviewIds = [];

    /** Seite des Formulars – der Link "KI-Auftraege" fuehrt dorthin zurueck */
    public string $aiPageUrl = '';

    /** @var array{title: string, html: string, prompt: string, usage: ?array, task?: array{met: bool, created: bool, parsed: bool, id: ?int, title: ?string, url: ?string}}|null */
    public ?array $aiResult = null;

    public ?string $aiError = null;

    /**
     * Bereich, z. B. "countries".
     */
    abstract protected function aiArea(): string;

    /**
     * Die aktuellen Werte des Formulars zu den Platzhaltern des Bereichs.
     *
     * @return array<string, mixed>
     */
    abstract protected function aiContext(): array;

    public function openAiCheck(string $section): void
    {
        if ($section !== AiAreas::GENERAL && ! isset(AiAreas::sectionLabels($this->aiArea())[$section])) {
            return;
        }

        $this->aiSection = $section;
        $this->aiResult = null;
        $this->aiError = null;
        unset($this->aiChecks, $this->aiData);

        // Genau eine Pruefung ist vorgewaehlt; ohne Pruefung steht der eigene Prompt bereit.
        $this->aiCheckId = match ($this->aiChecks->count()) {
            0 => 'custom',
            1 => (string) $this->aiChecks->first()->id,
            default => '',
        };
        $this->loadAiPromptDraft();
        $this->resetValidation();

        $this->modal('ai-check')->show();
    }

    public function updatedAiCheckId(): void
    {
        $this->aiResult = null;
        $this->aiError = null;
        $this->loadAiPromptDraft();
        $this->resetValidation();
    }

    /**
     * Den Prompt der gewaehlten Pruefung als Entwurf fuer diesen Lauf laden.
     */
    protected function loadAiPromptDraft(): void
    {
        $check = ctype_digit($this->aiCheckId) ? $this->aiChecks->firstWhere('id', (int) $this->aiCheckId) : null;

        $this->aiPromptDraft = (string) ($check?->prompt ?? '');
    }

    /**
     * Anpassungen am Prompt verwerfen – zurueck zum hinterlegten Text.
     */
    public function resetAiPrompt(): void
    {
        $this->loadAiPromptDraft();
        $this->resetValidation('aiPromptDraft');
    }

    /**
     * Modelle fuer den eigenen Prompt: die des Schluessels, ersatzweise die mit Preisen.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function aiModelOptions(): array
    {
        try {
            $ids = AiSettings::apiKey() ? array_column(app(OpenAiModelService::class)->chatModels(), 'id') : [];
        } catch (Throwable) {
            $ids = [];
        }

        return array_values($ids ?: array_keys(config('ai_prices.models', [])));
    }

    /**
     * Die Pruefungen des offenen Abschnitts.
     */
    #[Computed]
    public function aiChecks(): Collection
    {
        return $this->aiSection === '' ? collect() : AiCheck::query()->active()->forSection($this->aiArea(), $this->aiSection)->get();
    }

    /**
     * Die Daten, die an die KI gehen: Bezeichnung => Wert, nur die Platzhalter des Abschnitts.
     *
     * @return array<string, array{label: string, value: string}>
     */
    #[Computed]
    public function aiData(): array
    {
        if ($this->aiSection === '') {
            return [];
        }

        $labels = $this->aiReviewLabels($this->aiSection);
        $context = array_intersect_key($this->aiContext(), $labels);
        $service = app(AiCheckService::class);

        return array_map(fn (string $key) => ['label' => $labels[$key], 'value' => $service->format($context[$key] ?? null)], array_combine(array_keys($labels), array_keys($labels)));
    }

    /**
     * Seite merken und einen per Link angeforderten Auftrag vormerken.
     */
    public function mountRunsAiChecks(): void
    {
        // Nur der Pfad: der Link bleibt auf dieser Plattform.
        $this->aiPageUrl = '/'.ltrim(request()->path(), '/');
        $this->aiReviewToOpen = request()->integer('ki-pruefung') ?: null;
    }

    /**
     * Die KI recherchiert im Internet und prueft jedes Feld des offenen Abschnitts.
     * Das dauert oft ueber eine Minute: der Auftrag geht in die Queue, und es
     * lassen sich weitere einreichen. Ist er fertig, meldet er sich (Formular,
     * "KI-Auftraege"); Vorschlaege lassen sich dann uebernehmen.
     */
    public function reviewAiFields(): void
    {
        $this->aiResult = null;
        $this->aiError = null;

        if ($this->aiSection === '' || $this->aiSection === AiAreas::GENERAL) {
            $this->aiError = 'Die Feldprüfung gibt es je Abschnitt – bitte die Schaltfläche „KI“ an einem Abschnitt verwenden.';

            return;
        }

        $subject = $this->aiSubject();

        if ($this->aiOpenReviews->contains('section', $this->aiSection)) {
            $this->aiError = 'Für diesen Abschnitt läuft bereits eine Feldprüfung.';

            return;
        }

        $labels = $this->aiReviewLabels($this->aiSection);

        // Die Sammelangabe des Abschnitts (z. B. "Risikoprofil (alle Angaben)") waere neben ihren Einzelfeldern doppelt.
        if (count($labels) > 1) {
            unset($labels[$this->aiSection]);
        }

        $all = $this->aiContext();
        $context = array_intersect_key($all, $labels);

        $review = AiFieldReview::create([
            'user_id' => auth('web')->id(),
            'area' => $this->aiArea(),
            'section' => $this->aiSection,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'title' => $this->aiReviewTitle(),
            // Ein neuer, ungespeicherter Eintrag laesst sich spaeter nicht wieder oeffnen.
            'url' => $subject && $this->aiPageUrl !== '' ? $this->aiPageUrl : null,
            'model' => trim($this->aiCustomModel) ?: null,
            'status' => AiFieldReview::STATUS_QUEUED,
        ]);

        ReviewAiFieldsJob::dispatch(
            $review->id,
            $context,
            $labels,
            array_diff_key($all, $context),
            AiAreas::placeholders($this->aiArea(), null),
            $this->aiReviewHint($this->aiSection),
            $this->aiReviewFormats($this->aiSection),
        );

        unset($this->aiOpenReviews);
        $this->dispatch('adminv2-ai-reviews-changed');

        // Mit der Queue "sync" (Tests, lokal) ist der Auftrag schon fertig.
        $this->checkAiReviews();

        if ($this->aiReviewId !== $review->id) {
            $this->dispatch('adminv2-toast', message: 'Feldprüfung „'.$review->sectionLabel().'“ eingereicht – du kannst weiterarbeiten, sie meldet sich, wenn sie fertig ist.');
        }
    }

    /**
     * Wartende und laufende Auftraege dieses Eintrags.
     *
     * @return Collection<int, AiFieldReview>
     */
    #[Computed]
    public function aiOpenReviews(): Collection
    {
        return AiFieldReview::query()
            ->where('user_id', auth('web')->id())
            ->forSubject($this->aiArea(), $this->aiSubject())
            ->open()
            ->oldest()
            ->get();
    }

    /**
     * Fertige, noch nicht geoeffnete Auftraege dieses Eintrags.
     *
     * @return Collection<int, AiFieldReview>
     */
    #[Computed]
    public function aiFinishedReviews(): Collection
    {
        return AiFieldReview::query()
            ->where('user_id', auth('web')->id())
            ->forSubject($this->aiArea(), $this->aiSubject())
            ->unseenFinished()
            ->latest('finished_at')
            ->get();
    }

    /**
     * Wird abgefragt, solange Auftraege dieses Eintrags offen sind: ein fertiges
     * Ergebnis kommt ins Formular, sofern dort gerade keines steht; sonst
     * bleibt es zum Oeffnen in der Liste.
     */
    public function checkAiReviews(): void
    {
        unset($this->aiOpenReviews, $this->aiFinishedReviews);

        foreach ($this->aiFinishedReviews as $review) {
            if ($review->failureMessage() !== null) {
                $review->update(['seen_at' => now()]);
                $this->dispatch('adminv2-toast', message: 'Feldprüfung „'.$review->sectionLabel().'“ fehlgeschlagen: '.$review->failureMessage(), variant: 'danger');

                if ($review->section === $this->aiSection) {
                    $this->aiError = $review->failureMessage();
                }

                continue;
            }

            // Ins Formular, wenn dort nichts steht oder das Fenster genau diesen Abschnitt zeigt.
            if ($this->aiReview === null || $review->section === $this->aiSection) {
                $this->loadAiReview($review);
                $this->dispatch('adminv2-toast', message: 'Feldprüfung „'.$review->sectionLabel().'“ fertig – die Vorschläge stehen bereit.');
            } elseif (! in_array($review->id, $this->aiAnnouncedReviewIds, true)) {
                $this->aiAnnouncedReviewIds[] = $review->id;
                $this->dispatch('adminv2-toast', message: 'Feldprüfung „'.$review->sectionLabel().'“ fertig – im KI-Fenster unter „Fertige Prüfungen“ öffnen.');
            }
        }

        unset($this->aiOpenReviews, $this->aiFinishedReviews);
        $this->dispatch('adminv2-ai-reviews-changed');
    }

    /**
     * Ein fertiges Ergebnis oeffnen (Liste im Fenster, Link aus "KI-Auftraege").
     */
    public function openAiReview(int $id): void
    {
        $review = AiFieldReview::query()->where('user_id', auth('web')->id())->forSubject($this->aiArea(), $this->aiSubject())->find($id);

        if (! $review || ! $review->isFinished()) {
            return;
        }

        if ($review->failureMessage() !== null) {
            $review->update(['seen_at' => $review->seen_at ?? now()]);
            $this->openAiCheck($review->section);
            $this->aiCheckId = 'review';
            $this->aiError = $review->failureMessage();

            return;
        }

        $this->loadAiReview($review);
        $this->openAiCheck($review->section);
        $this->aiCheckId = 'review';
        $this->dispatch('adminv2-ai-reviews-changed');
    }

    /**
     * Den per Link angeforderten Auftrag oeffnen (beim Laden der Seite).
     */
    public function openRequestedAiReview(): void
    {
        $id = $this->aiReviewToOpen;
        $this->aiReviewToOpen = null;

        if ($id) {
            $this->openAiReview($id);
        }

        $this->checkAiReviews();
    }

    /**
     * Einen wartenden Auftrag zuruecknehmen bzw. einen laufenden verwerfen.
     */
    public function cancelAiReview(int $id): void
    {
        AiFieldReview::query()->where('user_id', auth('web')->id())->whereKey($id)->open()->delete();

        unset($this->aiOpenReviews);
        $this->dispatch('adminv2-ai-reviews-changed');
    }

    protected function loadAiReview(AiFieldReview $review): void
    {
        // Ein Ergebnis mit offenen Vorschlaegen geht nicht verloren: es rueckt zurueck in "Fertige Pruefungen".
        if ($this->aiReviewId && $this->aiReviewId !== $review->id && $this->aiReviewHasOpenItems()) {
            AiFieldReview::query()->where('user_id', auth('web')->id())->whereKey($this->aiReviewId)->update(['seen_at' => null]);
            $this->aiAnnouncedReviewIds[] = $this->aiReviewId;
            $this->dispatch('adminv2-toast', message: 'Die vorherige Feldprüfung „'.AiAreas::sectionLabel($this->aiArea(), $this->aiReview['section']).'“ hat noch offene Vorschläge – sie liegt im KI-Fenster unter „Feldprüfungen zu diesem Eintrag“.');
        }

        $this->aiReview = ['section' => $review->section] + (array) $review->result;
        $this->aiReviewId = $review->id;
        $this->markAppliedAiItems();
        $review->update(['seen_at' => $review->seen_at ?? now()]);
        unset($this->aiFinishedReviews);
    }

    /**
     * Was im Formular schon steht, gilt als uebernommen – auch nach dem Neuladen
     * eines Ergebnisses, dessen Vorschlaege laengst gespeichert sind.
     */
    protected function markAppliedAiItems(): void
    {
        $context = $this->aiContext();

        foreach ($this->aiReview['fields'] ?? [] as $key => $field) {
            if (($field['status'] ?? null) === AiFieldReviewService::STATUS_CHANGE && filled($field['value'] ?? null)
                && $this->aiSuggestionMatches($key, (string) $field['value'], $context)) {
                $this->aiReview['fields'][$key]['applied'] = true;
            }

            if (filled($field['note'] ?? null) && $this->aiNoteApplied($key, (string) $field['note'])) {
                $this->aiReview['fields'][$key]['note_applied'] = true;
            }
        }
    }

    /**
     * Hat das Feld schon den vorgeschlagenen Wert? Vergleicht ohne Gross-/Kleinschreibung
     * und Leerraum; Zahlen auch ohne Tausendertrennzeichen.
     *
     * @param  array<string, mixed>  $context  aktuelle Formularwerte (aiContext)
     */
    protected function aiSuggestionMatches(string $key, string $value, array $context): bool
    {
        if (! array_key_exists($key, $context)) {
            return false;
        }

        $normalize = fn (string $text) => mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $text)));
        $current = $normalize(app(AiCheckService::class)->format($context[$key]));
        $suggested = $normalize($value);

        if ($current === $suggested) {
            return true;
        }

        $digits = fn (string $text) => preg_replace('/[\s.,\']/u', '', $text);

        return is_numeric($digits($current)) && $digits($current) === $digits($suggested);
    }

    /**
     * Steht die Begruendung schon in der Notiz des Feldes? Ohne Notizen: false.
     */
    protected function aiNoteApplied(string $key, string $text): bool
    {
        return false;
    }

    /**
     * Steht im Formular ein Ergebnis mit noch nicht uebernommenen Vorschlaegen oder Notiztexten?
     */
    protected function aiReviewHasOpenItems(): bool
    {
        foreach ($this->aiReview['fields'] ?? [] as $field) {
            if (($field['status'] ?? null) === AiFieldReviewService::STATUS_CHANGE && ! ($field['applied'] ?? false)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Erlaubte Werte je Feld fuer die Feldpruefung, z. B. "Stufe 1–5" oder
     * "Liste, durch Komma getrennt" – Schluessel => Beschreibung. Ohne Angabe
     * gilt ein Feld als Freitext.
     *
     * @return array<string, string>
     */
    protected function aiReviewFormats(string $section): array
    {
        return [];
    }

    /**
     * Bezeichnung des Eintrags fuer die Liste der Auftraege, z. B. "Deutschland".
     */
    protected function aiReviewTitle(): ?string
    {
        $subject = $this->aiSubject();

        return match (true) {
            $subject === null => null,
            method_exists($subject, 'getName') => (string) $subject->getName('de'),
            default => (string) ($subject->getAttribute('name') ?? $subject->getKey()),
        };
    }

    /**
     * Einen Vorschlag der Feldpruefung in das Formular uebernehmen.
     */
    public function applyAiSuggestion(string $key): void
    {
        $field = $this->aiReview['fields'][$key] ?? null;

        if (! $field || $field['status'] !== AiFieldReviewService::STATUS_CHANGE || $field['value'] === null) {
            return;
        }

        if (! $this->aiApply($key, $field['value'])) {
            $this->dispatch('adminv2-toast', message: 'Dieser Vorschlag lässt sich nicht automatisch übernehmen – bitte von Hand eintragen.', variant: 'danger');

            return;
        }

        $this->aiReview['fields'][$key]['applied'] = true;
        $this->resetValidation();
    }

    public function applyAllAiSuggestions(): void
    {
        $applied = 0;
        $skipped = 0;

        foreach ($this->aiReview['fields'] ?? [] as $key => $field) {
            if ($field['status'] !== AiFieldReviewService::STATUS_CHANGE || ($field['applied'] ?? false)) {
                continue;
            }

            if ($this->aiApply($key, (string) $field['value'])) {
                $this->aiReview['fields'][$key]['applied'] = true;
                $applied++;
            } else {
                $skipped++;
            }
        }

        $this->resetValidation();
        $this->dispatch('adminv2-toast', message: $applied.' '.($applied === 1 ? 'Vorschlag' : 'Vorschläge').' übernommen'.($skipped ? ', '.$skipped.' nicht automatisch übernehmbar' : '').' – noch nicht gespeichert.');
    }

    /**
     * Die Begruendung der KI zu einem Feld in dessen Notiz uebernehmen.
     */
    public function applyAiNote(string $key): void
    {
        $field = $this->aiReview['fields'][$key] ?? null;

        if (! $field || blank($field['note'] ?? null) || ($field['note_applied'] ?? false)) {
            return;
        }

        if (! $this->aiApplyNote($key, $field['note'])) {
            $this->dispatch('adminv2-toast', message: 'Zu diesem Feld gibt es keine Notiz.', variant: 'danger');

            return;
        }

        $this->aiReview['fields'][$key]['note_applied'] = true;
    }

    public function applyAllAiNotes(): void
    {
        $applied = 0;

        foreach ($this->aiReview['fields'] ?? [] as $key => $field) {
            if (filled($field['note'] ?? null) && ! ($field['note_applied'] ?? false) && $this->aiApplyNote($key, $field['note'])) {
                $this->aiReview['fields'][$key]['note_applied'] = true;
                $applied++;
            }
        }

        $this->dispatch('adminv2-toast', message: $applied.' '.($applied === 1 ? 'Text' : 'Texte').' in die Notizen übernommen – noch nicht gespeichert.');
    }

    public function dismissAiReview(): void
    {
        $this->aiReview = null;
        $this->aiReviewId = null;

        // Ein weiteres fertiges Ergebnis rueckt nach.
        $this->checkAiReviews();
    }

    /**
     * Einen vorgeschlagenen Wert in das passende Formularfeld schreiben.
     * Liefert false, wenn das Feld nicht automatisch gesetzt werden kann.
     */
    protected function aiApply(string $key, string $value): bool
    {
        return false;
    }

    /**
     * Die Felder eines Abschnitts fuer die Feldpruefung: Schluessel => Bezeichnung.
     * Ueblicherweise die Platzhalter des Abschnitts; Formulare mit Listen
     * (Lounges, Hotels) nennen hier zusaetzlich die Felder jedes Eintrags.
     *
     * @return array<string, string>
     */
    protected function aiReviewLabels(string $section): array
    {
        return AiAreas::placeholders($this->aiArea(), $section);
    }

    /**
     * Der gespeicherte Datensatz des Formulars – an ihn haengt eine Pruefung,
     * die Aufgaben anlegt, ihre Unteraufgabe. Ohne ihn (neuer Eintrag) entsteht keine.
     */
    protected function aiSubject(): ?Model
    {
        return property_exists($this, 'recordId') && $this->recordId ? $this->record : null;
    }

    /**
     * Hinweis an die KI fuer die Feldpruefung eines Abschnitts – etwa wie seine
     * Felder zusammenhaengen oder welche Werte ein Feld annehmen darf.
     */
    protected function aiReviewHint(string $section): ?string
    {
        return null;
    }

    /**
     * Die Begruendung der KI in die Notiz des Feldes schreiben. Liefert false,
     * wenn das Feld keine Notiz hat.
     */
    protected function aiApplyNote(string $key, string $text): bool
    {
        return false;
    }

    /**
     * Ja/Nein-Angabe der KI.
     */
    protected function aiBool(string $value): bool
    {
        return in_array(mb_strtolower(trim($value)), ['ja', 'yes', 'true', '1', 'wahr'], true);
    }

    /**
     * Zahl der KI ohne Tausendertrennzeichen und Einheit, Dezimalpunkt bleibt.
     */
    protected function aiNumber(string $value): string
    {
        $clean = preg_replace('/[^0-9,.\-]/', '', $value) ?? '';

        // "83.200.000" -> 83200000; "357.588,5" -> 357588.5; "48.1351" bleibt.
        if (substr_count($clean, '.') > 1 || (str_contains($clean, '.') && str_contains($clean, ','))) {
            $clean = str_replace('.', '', $clean);
        }

        return str_replace(',', '.', $clean);
    }

    /**
     * Den Eintrag einer Auswahl finden, dessen Bezeichnung die KI genannt hat.
     *
     * @param  iterable<mixed>  $options
     * @param  callable(mixed): string  $label
     */
    protected function aiMatch(iterable $options, string $value, callable $label): mixed
    {
        $needle = mb_strtolower(trim($value));

        foreach ($options as $option) {
            if (mb_strtolower(trim($label($option))) === $needle) {
                return $option;
            }
        }

        return null;
    }

    public function runAiCheck(): void
    {
        $this->aiResult = null;
        $this->aiError = null;

        if ($this->aiCheckId === 'custom') {
            $this->validate([
                'aiCustomPrompt' => ['required', 'string', 'min:10', 'max:6000'],
                'aiCustomModel' => ['nullable', 'string', 'max:80'],
                'aiCheckName' => [Rule::requiredIf($this->aiSaveAsCheck), 'nullable', 'string', 'max:100'],
            ], [
                'aiCustomPrompt.required' => 'Bitte einen Prompt eingeben.',
                'aiCustomPrompt.min' => 'Der Prompt ist zu kurz.',
                'aiCheckName.required' => 'Bitte einen Namen für die Prüfung eingeben.',
            ]);

            // Ein nicht gespeicherter Prompt – oder, auf Wunsch, ab jetzt eine Pruefung dieses Abschnitts.
            $check = new AiCheck([
                'name' => trim($this->aiCheckName) ?: 'Eigener Prompt',
                'area' => $this->aiArea(),
                'section' => $this->aiSection,
                'prompt' => trim($this->aiCustomPrompt),
                'model' => trim($this->aiCustomModel) ?: null,
                'created_by' => auth('web')->id(),
            ]);

            if ($this->aiSaveAsCheck) {
                $check->save();
                $this->aiSaveAsCheck = false;
                $this->aiCheckName = '';
                unset($this->aiChecks);
                $this->dispatch('adminv2-toast', message: 'Prompt als Prüfung „'.$check->name.'“ gespeichert.');
            }
        } else {
            $check = $this->aiChecks->firstWhere('id', (int) $this->aiCheckId);

            if ($check && trim($this->aiPromptDraft) !== trim((string) $check->prompt)) {
                $this->validate([
                    'aiPromptDraft' => ['required', 'string', 'min:10', 'max:6000'],
                ], [
                    'aiPromptDraft.required' => 'Bitte einen Prompt eingeben – oder den hinterlegten wiederherstellen.',
                    'aiPromptDraft.min' => 'Der Prompt ist zu kurz.',
                    'aiPromptDraft.max' => 'Der Prompt ist zu lang (höchstens 6000 Zeichen).',
                ]);

                // Nur fuer diesen Lauf: eine Kopie, die nicht gespeichert wird. Die hinterlegte Pruefung bleibt unveraendert.
                $name = $check->name.' (Prompt angepasst)';
                $check = $check->replicate()->fill(['prompt' => trim($this->aiPromptDraft), 'name' => $name]);
            }
        }

        if (! $check) {
            $this->aiError = 'Bitte zuerst eine Prüfung auswählen oder einen eigenen Prompt eingeben.';

            return;
        }

        set_time_limit(120);

        $labels = AiAreas::placeholders($this->aiArea(), $this->aiSection);
        $all = $this->aiContext();
        $context = array_intersect_key($all, $labels);

        // Legt die Pruefung Aufgaben an, beurteilt die KI zugleich ihre Bedingung fuer diesen Eintrag.
        // Ein fuer diesen Lauf angepasster Prompt und ein eigener Prompt legen keine Aufgaben an.
        $subject = $check->createsTasks() ? $this->aiSubject() : null;

        try {
            // Platzhalter wie {name} funktionieren in jedem Abschnitt; die Datenliste bleibt beim Abschnitt.
            $result = $subject
                ? app(AiCheckTaskService::class)->run($check, $subject, $context, $labels, array_diff_key($all, $context), auth('web')->id())
                : app(AiCheckService::class)->run($check, $context, $labels, array_diff_key($all, $context));
        } catch (Throwable $exception) {
            $this->aiError = $exception->getMessage();

            return;
        }

        $this->aiResult = ['title' => $check->name] + $result;

        if ($result['task']['id'] ?? null) {
            // Die Karte "Aufgaben" auf der Seite zeigt die neue bzw. ergaenzte Aufgabe.
            $this->dispatch('adminv2-tasks-changed');
        }
    }
}
