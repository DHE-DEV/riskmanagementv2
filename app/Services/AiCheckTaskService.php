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
 * Pruefung, nennt die Aenderungen, die sie am Datensatz vorschlaegt, und
 * beurteilt die Bedingung der Pruefung. Ohne eigene Bedingung gilt: schlaegt
 * sie Aenderungen vor, entsteht unter der Sammelaufgabe der Pruefung eine
 * Unteraufgabe mit Bezug auf den Datensatz und den Vorschlaegen – je Datensatz
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
            $check->taskCondition(),
            '',
            'Antworte ausschließlich mit JSON in genau dieser Form, ohne Text davor oder danach:',
            '{"answer": "<deine Antwort auf den Auftrag oben als Text, Markdown ist erlaubt>", "issues": ["<eine Beanstandung in einem Satz – konkret, mit der betroffenen Angabe>"], "changes": [{"field": "<Bezeichnung der Angabe>", "current": "<bisheriger Wert, leer wenn nichts eingetragen ist>", "proposed": "<vorgeschlagener Wert>", "reason": "<kurze Begründung>"}], "met": true | false, "title": "<kurzer Titel der Aufgabe, höchstens 120 Zeichen>", "description": "<was konkret zu tun ist und warum>"}',
            'In "issues" steht jede Beanstandung einzeln: was genau am Eintrag falsch, veraltet, widersprüchlich oder unvollständig ist – ohne Beanstandung eine leere Liste.',
            'In "changes" steht jede Änderung, die du am Eintrag vorschlägst, einzeln – nur was du verlässlich weißt; ohne Vorschläge eine leere Liste.',
            '"met" ist nur dann true, wenn die Bedingung eindeutig zutrifft – im Zweifel false. "title" und "description" nur bei true füllen, sonst leer lassen.',
        ]);
    }

    /**
     * @return array{answer: string, met: bool, title: ?string, description: ?string, issues: array<int, string>, changes: array<int, array{field: string, current: ?string, proposed: string, reason: ?string}>, parsed: bool}
     */
    public function parse(string $answer): array
    {
        // Das Modell rahmt JSON gelegentlich mit ```json … ``` ein.
        $data = preg_match('/\{.*\}/s', $answer, $match) ? json_decode($match[0], true) : null;

        if (! is_array($data) || ! array_key_exists('met', $data)) {
            return ['answer' => trim($answer), 'met' => false, 'title' => null, 'description' => null, 'issues' => [], 'changes' => [], 'parsed' => false];
        }

        $text = fn (mixed $value) => is_scalar($value) && trim((string) $value) !== '' ? trim((string) $value) : null;

        // Ein Vorschlag ohne Feld oder ohne neuen Wert ist keiner.
        $changes = [];
        foreach (is_array($data['changes'] ?? null) ? $data['changes'] : [] as $change) {
            if (is_array($change) && $text($change['field'] ?? null) !== null && $text($change['proposed'] ?? null) !== null) {
                $changes[] = [
                    'field' => $text($change['field']),
                    'current' => $text($change['current'] ?? null),
                    'proposed' => $text($change['proposed']),
                    'reason' => $text($change['reason'] ?? null),
                ];
            }
        }

        // Beanstandungen: je eine Zeile; das Modell liefert gelegentlich einen einzelnen Text statt einer Liste.
        $issues = array_values(array_filter(array_map($text, is_array($data['issues'] ?? null) ? $data['issues'] : [$data['issues'] ?? null])));

        return [
            'answer' => $text($data['answer'] ?? null) ?? '',
            'met' => filter_var($data['met'], FILTER_VALIDATE_BOOLEAN),
            'title' => $text($data['title'] ?? null),
            'description' => $text($data['description'] ?? null),
            'issues' => $issues,
            'changes' => $changes,
            'parsed' => true,
        ];
    }

    /**
     * Beschreibung der Aufgabe: was zu tun ist, was die KI genau beanstandet
     * und welche Aenderungen sie vorschlaegt. Nennt die KI keine einzelnen
     * Beanstandungen, steht stattdessen ihre Antwort als Befund da.
     *
     * @param  array{answer: string, description: ?string, issues?: array<int, string>, changes?: array<int, array{field: string, current: ?string, proposed: string, reason: ?string}>}  $result
     */
    public function describeResult(array $result): string
    {
        $todo = $result['description'] ?? '';
        $issues = $result['issues'] ?? [];
        $changes = $result['changes'] ?? [];
        $answer = $result['answer'];

        $parts = [$todo];

        if ($issues !== []) {
            $parts[] = "Beanstandungen der KI:\n".implode("\n", array_map(fn (string $issue) => '- '.$issue, $issues));
        } elseif ($answer !== '' && $answer !== $todo) {
            $parts[] = ($todo !== '' ? "Befund der KI:\n" : '').$answer;
        }

        if ($changes !== []) {
            $parts[] = "Vorgeschlagene Änderungen:\n".$this->describeChanges($changes);
        }

        return trim(implode("\n\n", array_filter($parts, fn (string $part) => $part !== '')));
    }

    /**
     * Die vorgeschlagenen Aenderungen als Liste fuer die Aufgabe.
     *
     * @param  array<int, array{field: string, current: ?string, proposed: string, reason: ?string}>  $changes
     */
    public function describeChanges(array $changes): string
    {
        return implode("\n", array_map(fn (array $change) => '- '.$change['field'].': '
            .($change['current'] !== null ? 'bisher „'.$change['current'].'“, ' : 'bisher leer, ')
            .'Vorschlag „'.$change['proposed'].'“'
            .($change['reason'] !== null ? ' – '.$change['reason'] : ''), $changes));
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
     * @param  array{answer: string, met: bool, title: ?string, description: ?string, changes?: array<int, array{field: string, current: ?string, proposed: string, reason: ?string}>, parsed: bool}  $result
     * @return array{met: bool, created: bool, parsed: bool, id: ?int, title: ?string, url: ?string}
     */
    public function apply(AiCheck $check, Model $subject, array $result, ?int $userId = null): array
    {
        $changes = $result['changes'] ?? [];

        // Ohne eigene Bedingung zaehlt, ob die Pruefung Aenderungen vorschlaegt – auch wenn "met" dazu nicht passt.
        $met = $check->hasTaskCondition() ? $result['met'] : ($result['met'] || $changes !== []);

        $outcome = ['met' => $met, 'created' => false, 'parsed' => $result['parsed'], 'id' => null, 'title' => null, 'url' => null];

        if (! $result['parsed'] || ! $met) {
            return $outcome;
        }

        // Unteraufgabe der Sammelaufgabe – oder eine eigenstaendige Aufgabe je Datensatz.
        $parent = $check->createsSingleTasks() ? null : $this->parentTask($check, $userId);

        $fallbackTitle = match (count($changes)) {
            0 => $check->name,
            1 => 'Änderung vorgeschlagen: '.$changes[0]['field'],
            default => count($changes).' Änderungen vorgeschlagen',
        };
        $title = Str::limit(TaskSubjects::title($subject).': '.($result['title'] ?? $fallbackTitle), 250, '…');

        $description = $this->describeResult($result);

        $existing = AdminTask::query()
            ->where('ai_check_id', $check->id)
            ->when($parent, fn ($query) => $query->where('parent_id', $parent->id), fn ($query) => $query->whereNull('parent_id'))
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
        if ($parent?->isDone()) {
            $parent->update(['status' => AdminTask::STATUS_OPEN]);
        }

        // Unteraufgaben erben von der Sammelaufgabe; Einzelaufgaben bekommen die Einstellungen der Pruefung.
        $task = AdminTask::create([
            'title' => $title,
            'description' => $description !== '' ? $description : null,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'subject_token' => null,
            'ai_check_id' => $check->id,
            'created_by' => $userId,
        ] + ($parent ? $parent->subtaskDefaults() : $this->taskAttributes($check->task_settings ?? [], $userId ?? $check->created_by)));

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
            $parent = $this->createParent($check, [], $userId);
        } elseif ($parent->ai_check_id !== $check->id) {
            // Eine vorhandene Aufgabe wurde als Sammelaufgabe gewaehlt.
            $parent->forceFill(['ai_check_id' => $check->id])->saveQuietly();
        }

        return $parent;
    }

    /**
     * Die Einstellungen einer Aufgabe, die die Pruefung anlegt: Rubrik,
     * Prioritaet, Verantwortung, naechster Bearbeiter und Faelligkeit. Was
     * fehlt, bekommt eine sinnvolle Vorgabe.
     *
     * @param  array{category_id?: int|string|null, priority?: ?string, responsible?: ?string, next_assignee?: ?string, due_days?: int|string|null}  $settings
     * @return array<string, mixed>
     */
    public function taskAttributes(array $settings, ?int $userId = null): array
    {
        $category = AdminTaskCategory::query()->find((int) ($settings['category_id'] ?? 0))
            ?? AdminTaskCategory::query()->where('name', 'Stammdaten')->first()
            ?? AdminTaskCategory::query()->ordered()->firstOrFail();

        [$responsibleId, $responsibleTeamId] = AdminTask::parseAssignee($settings['responsible'] ?? null);
        [$nextId, $nextTeamId] = AdminTask::parseAssignee($settings['next_assignee'] ?? null);

        if (! $responsibleId && ! $responsibleTeamId) {
            $responsibleId = $userId ?? AdminTask::assignableUsers()->first()?->id;
        }

        $dueDays = $settings['due_days'] ?? null;

        return [
            'category_id' => $category->id,
            'priority' => isset(AdminTask::priorityOptions()[$settings['priority'] ?? '']) ? $settings['priority'] : AdminTask::PRIORITY_NORMAL,
            'responsible_id' => $responsibleId,
            'responsible_team_id' => $responsibleTeamId,
            'next_assignee_id' => $nextId,
            'next_assignee_team_id' => $nextTeamId,
            // Faellig eine feste Zahl von Tagen nach dem Anlegen.
            'due_date' => is_numeric($dueDays) ? now()->addDays((int) $dueDays)->format('Y-m-d') : null,
        ];
    }

    /**
     * Eine neue Sammelaufgabe fuer die Pruefung anlegen – mit den gewuenschten
     * Einstellungen; ihre Unteraufgaben erben sie.
     *
     * @param  array{title?: ?string, category_id?: int|string|null, priority?: ?string, responsible?: ?string, next_assignee?: ?string, due_days?: int|string|null}  $settings
     */
    public function createParent(AiCheck $check, array $settings = [], ?int $userId = null): AdminTask
    {
        $parent = AdminTask::create([
            'title' => Str::limit(filled($settings['title'] ?? null) ? trim($settings['title']) : 'KI-Prüfung „'.$check->name.'“ – '.$this->scopeLabel($check), 250, '…'),
            'description' => 'Sammelaufgabe der KI-Prüfung „'.$check->name.'“ ('.$this->scopeLabel($check).").\n\n"
                .($check->hasTaskCondition()
                    ? 'Bedingung: '.$check->taskCondition()."\n\nTrifft die Bedingung bei einem Eintrag zu, entsteht hier eine Unteraufgabe mit Bezug auf diesen Eintrag."
                    : 'Schlägt die Prüfung bei einem Eintrag Änderungen vor, entsteht hier eine Unteraufgabe mit Bezug auf diesen Eintrag und den Vorschlägen.'),
            'created_by' => $userId ?? $check->created_by,
            'ai_check_id' => $check->id,
        ] + $this->taskAttributes($settings, $userId ?? $check->created_by));

        $check->forceFill(['task_mode' => AiCheck::TASK_MODE_PARENT, 'task_parent_id' => $parent->id])->save();

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
