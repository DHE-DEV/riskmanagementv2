<?php

namespace App\Livewire\AdminV2\Concerns;

use App\Models\AiPrompt;
use App\Services\ChatGptService;
use App\Support\AdminV2\RichText;
use App\Support\AiSettings;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Throwable;

/**
 * KI-Assistent der Stammdaten: eine hinterlegte Aufgabe (KI-Prompt) mit den
 * Daten des geoeffneten Eintrags ausfuehren und das Ergebnis anzeigen.
 */
trait RunsAiAssistant
{
    public string $aiPromptId = '';

    /** @var array{title: string, html: string, usage: ?array}|null */
    public ?array $aiResult = null;

    public ?string $aiError = null;

    /**
     * Model-Typ der KI-Prompts, z. B. "Country".
     */
    abstract protected function aiModelType(): string;

    /**
     * Werte fuer die Platzhalter des Prompts, z. B. {name}.
     *
     * @return array<string, mixed>
     */
    abstract protected function aiPlaceholderData(): array;

    #[Computed]
    public function aiPrompts(): Collection
    {
        return AiPrompt::query()->active()->forModel($this->aiModelType())->ordered()->get();
    }

    public function updatedAiPromptId(): void
    {
        $this->aiResult = null;
        $this->aiError = null;
    }

    public function runAiAssistant(): void
    {
        $this->aiResult = null;
        $this->aiError = null;

        $prompt = $this->aiPrompts->firstWhere('id', (int) $this->aiPromptId);

        if (! $prompt) {
            $this->aiError = 'Bitte zuerst eine Aufgabe auswählen.';

            return;
        }

        try {
            $ai = app(ChatGptService::class);
            $answer = $ai->processPrompt($prompt, $this->aiPlaceholderData());
            $usage = $ai->lastUsage();
        } catch (Throwable $exception) {
            $this->aiError = $exception->getMessage();

            return;
        }

        // Reiner Text behaelt seine Zeilenumbrueche; HTML wird bereinigt.
        $html = $answer === strip_tags($answer) ? nl2br(e(trim($answer))) : (string) RichText::sanitize($answer);

        $this->aiResult = [
            'title' => $prompt->name,
            'html' => $html,
            'usage' => $usage ? $usage + [
                'cost' => AiSettings::cost($usage['model'], $usage['input_tokens'], $usage['output_tokens']),
            ] : null,
        ];
    }
}
