<?php

namespace App\Livewire\AdminV2;

use App\Jobs\AskAiQuestionJob;
use App\Livewire\AdminV2\Concerns\AuthorizesAdminV2;
use App\Models\AiQuestion;
use App\Services\Ai\ClaudeService;
use App\Services\Ai\GeminiService;
use App\Services\OpenAiModelService;
use App\Support\AdminV2\AiAreas;
use App\Support\AiProviders;
use App\Support\AiSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

/**
 * Freie Frage an die KI unter einem Abschnitt eines Eintrags: Anbieter und
 * Modell waehlen, absenden – die Antwort kommt im Hintergrund (Queue) und
 * erscheint unter dem Fragefeld. Jeder Abschnitt hat sein eigenes Feld, es
 * lassen sich also mehrere Fragen gleichzeitig stellen.
 */
class AiQuestionBox extends Component
{
    use AuthorizesAdminV2;

    /** So viele Fragen stehen je Abschnitt unter dem Feld. */
    public const LIMIT = 5;

    /** Anbieter => Bezeichnung in der Auswahl */
    public const PROVIDER_LABELS = [
        AiProviders::OPENAI => 'ChatGPT',
        AiProviders::ANTHROPIC => 'Claude',
        AiProviders::GEMINI => 'Gemini',
    ];

    #[Locked]
    public string $area = '';

    #[Locked]
    public string $section = '';

    /** Der gespeicherte Eintrag, zu dem gefragt wird. */
    public Model $subject;

    /** Bezeichnung des Eintrags, z. B. "Spanien" – geht als Bezug mit an die KI. */
    #[Locked]
    public ?string $title = null;

    public string $question = '';

    public string $provider = '';

    public string $model = '';

    /** @var array<int, int> beim letzten Abfragen noch offene Fragen */
    public array $openIds = [];

    public function mount(): void
    {
        $this->provider = (string) array_key_first($this->providers);
        $this->model = $this->defaultModel();
        $this->openIds = $this->questions->filter->isOpen()->pluck('id')->all();
    }

    public function updatedProvider(): void
    {
        unset($this->modelOptions);
        $this->model = $this->defaultModel();
        $this->resetValidation(['provider', 'model']);
    }

    /**
     * Anbieter, fuer die ein Schluessel hinterlegt ist.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function providers(): array
    {
        return array_filter(self::PROVIDER_LABELS, fn (string $label, string $provider) => filled(AiProviders::apiKey($provider)), ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Die Modelle des gewaehlten Anbieters: Modell-ID => Bezeichnung.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function modelOptions(): array
    {
        return match ($this->provider) {
            AiProviders::OPENAI => $this->openAiModels(),
            AiProviders::ANTHROPIC => ClaudeService::MODELS,
            AiProviders::GEMINI => $this->geminiModels(),
            default => [],
        };
    }

    /**
     * Die letzten Fragen zu diesem Abschnitt, neueste zuerst.
     *
     * @return Collection<int, AiQuestion>
     */
    #[Computed]
    public function questions(): Collection
    {
        return AiQuestion::query()
            ->where('user_id', auth('web')->id())
            ->forSection($this->area, $this->section, $this->subject)
            ->latest('id')
            ->limit(self::LIMIT)
            ->get();
    }

    public function ask(): void
    {
        $this->validate([
            'question' => ['required', 'string', 'min:3', 'max:6000'],
            'provider' => ['required', Rule::in(array_keys($this->providers))],
            'model' => ['required', 'string', Rule::in(array_keys($this->modelOptions))],
        ], [
            'question.required' => 'Bitte eine Frage eingeben.',
            'question.min' => 'Die Frage ist zu kurz.',
            'question.max' => 'Die Frage ist zu lang (höchstens 6000 Zeichen).',
            'provider.required' => 'Bitte eine KI wählen.',
            'provider.in' => 'Für diese KI ist kein API-Schlüssel hinterlegt (System > KI).',
            'model.required' => 'Bitte ein Modell wählen.',
            'model.in' => 'Dieses Modell steht für die gewählte KI nicht zur Auswahl.',
        ]);

        $question = AiQuestion::create([
            'user_id' => auth('web')->id(),
            'area' => $this->area,
            'section' => $this->section,
            'subject_type' => $this->subject->getMorphClass(),
            'subject_id' => $this->subject->getKey(),
            'title' => $this->title,
            'provider' => $this->provider,
            'model' => $this->model,
            'question' => trim($this->question),
            'status' => AiQuestion::STATUS_QUEUED,
        ]);

        $this->openIds[] = $question->id;
        $this->question = '';

        AskAiQuestionJob::dispatch($question->id);

        unset($this->questions);
    }

    /**
     * Wird abgefragt, solange Fragen offen sind: fertige melden sich per Hinweis.
     */
    public function refreshQuestions(): void
    {
        unset($this->questions);

        $finished = AiQuestion::query()
            ->where('user_id', auth('web')->id())
            ->whereKey($this->openIds)
            ->get()
            ->reject->isOpen();

        foreach ($finished as $question) {
            $this->dispatch('adminv2-toast',
                message: 'KI-Antwort „'.$question->sectionLabel().'“ '.($question->failureMessage() ? 'fehlgeschlagen.' : 'ist da.'),
                variant: $question->failureMessage() ? 'danger' : 'success',
            );
        }

        $this->openIds = array_values(array_diff($this->openIds, $finished->pluck('id')->all()));
    }

    public function deleteQuestion(int $id): void
    {
        AiQuestion::query()->where('user_id', auth('web')->id())->whereKey($id)->delete();

        $this->openIds = array_values(array_diff($this->openIds, [$id]));
        unset($this->questions);
    }

    public function render(): View
    {
        return view('livewire.admin-v2.ai-question-box', [
            'sectionLabel' => AiAreas::sectionLabel($this->area, $this->section),
        ]);
    }

    /**
     * Das voreingestellte Modell des Anbieters: bei ChatGPT das unter System > KI
     * gewaehlte, sonst das erste der Liste.
     */
    protected function defaultModel(): string
    {
        $options = array_keys($this->modelOptions);

        if ($this->provider === AiProviders::OPENAI && in_array(AiSettings::model(), $options, true)) {
            return AiSettings::model();
        }

        return (string) ($options[0] ?? '');
    }

    /**
     * Die Modelle des OpenAI-Schluessels, ersatzweise die der Preisliste.
     *
     * @return array<string, string>
     */
    protected function openAiModels(): array
    {
        try {
            $ids = array_column(app(OpenAiModelService::class)->chatModels(), 'id');
        } catch (Throwable) {
            $ids = [];
        }

        $ids = $ids ?: array_values(array_filter(
            array_keys(config('ai_prices.models', [])),
            fn (string $id) => AiProviders::forModel($id) === AiProviders::OPENAI,
        ));

        return array_combine($ids, $ids) ?: [];
    }

    /**
     * @return array<string, string>
     */
    protected function geminiModels(): array
    {
        try {
            $models = app(GeminiService::class)->models();
        } catch (Throwable) {
            return [];
        }

        return collect($models)->mapWithKeys(fn (string $label, string $id) => [$id => $label !== '' ? $label.' ('.$id.')' : $id])->all();
    }
}
