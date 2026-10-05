<?php

namespace App\Services;

use App\Models\AdminTask;
use App\Models\AdminTaskActivity;
use App\Models\AdminTaskCategory;
use App\Models\AiCheck;
use App\Support\AdminV2\TaskSubjects;
use App\Support\AiSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * KI-Pruefungen, die Aufgaben anlegen: die KI beantwortet den Auftrag der
 * Pruefung und beurteilt zugleich, ob die hinterlegte Bedingung auf den
 * Datensatz zutrifft. Trifft sie zu, entsteht unter der Sammelaufgabe der
 * Pruefung eine Unteraufgabe mit Bezug auf den Datensatz – je Datensatz
 * hoechstens eine offene; eine vorhandene bekommt das neue Ergebnis als Notiz.
 */
class AiCheckTaskService
{
    public function __construct(protected AiCheckService $checks) {}

    /**
     * Der Prompt der Pruefung, ergaenzt um die Bedingung und die Antwortform.
     *
     * @param  array<string, mixed>  $context
     * @param  array<string, string>  $labels
     * @param  array<string, mixed>  $extra
     */
    public function buildPrompt(AiCheck $check, array $context, array $labels, array $extra = []): string
    {
        return $this->checks->buildPrompt($check->prompt, $context, $labels, $extra)."\n\n".implode("\n", [
            '---',
            'Beurteile zusätzlich, ob auf diesen Eintrag die folgende Bedingung zutrifft:',
            trim((string) $check->task_condition),
            '',
            'Antworte ausschließlich mit JSON in genau dieser Form, ohne Text davor oder danach:',
            '{"answer": "<deine Antwort auf den Auftrag oben als Text, Markdown ist erlaubt>", "met": true | false, "title": "<kurzer Titel der Aufgabe, höchstens 120 Zeichen>", "description": "<was konkret zu tun ist und warum>"}',
            '"met" ist nur dann true, wenn die Bedingung eindeutig zutrifft – im Zweifel false. "title" und "description" nur bei true füllen, sonst leer lassen.',
        ]);
    }

    /**
     * @return array{answer: string, met: bool, title: ?string, description: ?string, parsed: bool}
     */
    public function parse(string $answer): array
    {
        // Das Modell rahmt JSON gelegentlich mit ```json … ``` ein.
        $data = preg_match('/\{.*\}/s', $answer, $match) ? json_decode($match[0], true) : null;

        if (! is_array($data) || ! array_key_exists('met', $data)) {
            return ['answer' => trim($answer), 'met' => false, 'title' => null, 'description' => null, 'parsed' => false];
        }

        $text = fn (string $key) => is_scalar($data[$key] ?? null) && trim((string) $data[$key]) !== '' ? trim((string) $data[$key]) : null;

        return [
            'answer' => $text('answer') ?? '',
            'met' => filter_var($data['met'], FILTER_VALIDATE_BOOLEAN),
            'title' => $text('title'),
            'description' => $text('description'),
            'parsed' => true,
        ];
    }

    /**
     * Die Pruefung an einem Datensatz ausfuehren – Antwort wie bei jeder
     * Pruefung, dazu was aus der Bedingung wurde.
     *
     * @param  array<string, mixed>  $context
     * @param  array<string, string>  $labels
     * @param  array<string, mixed>  $extra
     * @return array{html: string, prompt: string, usage: ?array, task: array{met: bool, created: bool, parsed: bool, id: ?int, title: ?string, url: ?string}}
     */
    public function run(AiCheck $check, Model $subject, array $context, array $labels, array $extra = [], ?int $userId = null): array
    {
        $prompt = $this->buildPrompt($check, $context, $labels, $extra);

        $ai = app(ChatGptService::class);
        $answer = $ai->sendPrompt($prompt, ['model' => $check->model ?: AiSettings::model()]);
        $usage = $ai->lastUsage();

        $result = $this->parse($answer);

        return [
            'html' => $this->checks->toHtml($result['answer'] !== '' ? $result['answer'] : '–'),
            'prompt' => $prompt,
            'usage' => $usage ? $usage + ['cost' => AiSettings::cost($usage['model'], $usage['input_tokens'], $usage['output_tokens'])] : null,
            'task' => $this->apply($check, $subject, $result, $userId),
        ];
    }

    /**
     * Was aus dem Ergebnis folgt: nichts, eine neue Unteraufgabe oder eine
     * Notiz an der schon offenen Unteraufgabe zu diesem Datensatz.
     *
     * @param  array{answer: string, met: bool, title: ?string, description: ?string, parsed: bool}  $result
     * @return array{met: bool, created: bool, parsed: bool, id: ?int, title: ?string, url: ?string}
     */
    public function apply(AiCheck $check, Model $subject, array $result, ?int $userId = null): array
    {
        $outcome = ['met' => $result['met'], 'created' => false, 'parsed' => $result['parsed'], 'id' => null, 'title' => null, 'url' => null];

        if (! $result['parsed'] || ! $result['met']) {
            return $outcome;
        }

        $parent = $this->parentTask($check, $userId);
        $title = Str::limit(TaskSubjects::title($subject).': '.($result['title'] ?? $check->name), 250, '…');
        $description = $result['description'] ?? $result['answer'];

        $existing = AdminTask::query()
            ->where('ai_check_id', $check->id)
            ->where('parent_id', $parent->id)
            ->forSubject($subject)
            ->where('status', '!=', AdminTask::STATUS_DONE)
            ->first();

        if ($existing) {
            $existing->activities()->create([
                'user_id' => $userId,
                'type' => AdminTaskActivity::TYPE_NOTE,
                'body' => trim('Die KI-Prüfung „'.$check->name.'“ war erneut auffällig: '.($result['title'] ?? '')."\n\n".$description),
            ]);
            $existing->touch();

            return ['id' => $existing->id, 'title' => $existing->title, 'url' => route('adminv2.tasks.show', $existing)] + $outcome;
        }

        // Kommt Neues hinzu, ist die Sammelaufgabe nicht (mehr) erledigt.
        if ($parent->isDone()) {
            $parent->update(['status' => AdminTask::STATUS_OPEN]);
        }

        $task = AdminTask::create([
            'title' => $title,
            'description' => $description !== '' ? $description : null,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'subject_token' => null,
            'ai_check_id' => $check->id,
            'created_by' => $userId,
        ] + $parent->subtaskDefaults());

        return ['created' => true, 'id' => $task->id, 'title' => $task->title, 'url' => route('adminv2.tasks.show', $task)] + $outcome;
    }

    /**
     * Die Sammelaufgabe der Pruefung – sie bezieht sich auf alle Eintraege des
     * Bereichs. Fehlt sie (noch nie angelegt oder geloescht), entsteht sie neu.
     */
    public function parentTask(AiCheck $check, ?int $userId = null): AdminTask
    {
        $parent = $check->task_parent_id ? AdminTask::find($check->task_parent_id) : null;

        if (! $parent) {
            $category = AdminTaskCategory::query()->where('name', 'Stammdaten')->first() ?? AdminTaskCategory::query()->ordered()->firstOrFail();

            $parent = AdminTask::create([
                'title' => Str::limit('KI-Prüfung „'.$check->name.'“ – '.$this->scopeLabel($check), 250, '…'),
                'description' => 'Sammelaufgabe der KI-Prüfung „'.$check->name.'“ ('.$this->scopeLabel($check).").\n\n"
                    .'Bedingung: '.trim((string) $check->task_condition)."\n\n"
                    .'Trifft die Bedingung bei einem Eintrag zu, entsteht hier eine Unteraufgabe mit Bezug auf diesen Eintrag.',
                'category_id' => $category->id,
                'created_by' => $userId ?? $check->created_by,
                'responsible_id' => $userId ?? $check->created_by ?? AdminTask::assignableUsers()->first()?->id,
                'ai_check_id' => $check->id,
            ]);

            $check->forceFill(['task_parent_id' => $parent->id])->save();
        } elseif ($parent->ai_check_id !== $check->id) {
            // Eine vorhandene Aufgabe wurde als Sammelaufgabe gewaehlt.
            $parent->forceFill(['ai_check_id' => $check->id])->saveQuietly();
        }

        return $parent;
    }

    /**
     * Worauf sich die Pruefung bezieht, z. B. "Flughäfen › Lounges".
     */
    public function scopeLabel(AiCheck $check): string
    {
        return $check->areaLabel().(filled($check->section) ? ' › '.$check->sectionLabel() : '');
    }
}
