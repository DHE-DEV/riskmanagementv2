<?php

namespace App\Services;

use App\Models\AiEventSearch;
use App\Models\AiEventSearchProfile;
use App\Models\AiEventSuggestion;
use App\Models\Country;
use App\Models\CustomEvent;
use App\Models\EventType;
use App\Support\AdminV2\EventState;
use App\Support\AdminV2\RichText;
use App\Support\AiSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * KI-Suche nach aktuellen Ereignissen.
 *
 * Die KI sucht im Internet nach dem unter System > KI hinterlegten Auftrag
 * und liefert Themen samt Einschaetzung (Prioritaet, Zeitraum, Event-Typen,
 * Laender, Quellen). Dasselbe Ereignis aus mehreren Quellen ergibt EINEN
 * Vorschlag; bereits Vorgeschlagenes und – auf Wunsch – bereits Erfasstes
 * kommt nicht noch einmal.
 */
class AiEventSearchService
{
    /** Ab dieser Aehnlichkeit der Titel (Prozent) gelten zwei Meldungen als dasselbe Ereignis. */
    private const SAME_TITLE_PERCENT = 65;

    /** Wie weit zurueck fruehere Vorschlaege als "schon vorgeschlagen" gelten (Tage). */
    private const REMEMBER_SUGGESTIONS_DAYS = 14;

    private const MAX_RESULTS = 15;

    /** Fuer einen Lauf gemerkt: was es schon gibt. */
    private ?Collection $knownTopics = null;

    /**
     * Einen angelegten Suchlauf ausfuehren.
     */
    public function run(AiEventSearch $search): AiEventSearch
    {
        $this->knownTopics = null;

        try {
            $ai = app(ChatGptService::class);
            $answer = $ai->searchWeb($this->buildPrompt(
                $search->exclude_existing,
                $search->isTargeted() ? $search->filters : null,
                $search->prompt,
            ));

            $found = $this->parse($answer);
            $new = $this->store($search, $found);

            $usage = $ai->lastUsage();

            $search->update([
                'status' => AiEventSearch::STATUS_DONE,
                'found_count' => count($found),
                'new_count' => $new,
                'model' => $usage['model'] ?? null,
                'input_tokens' => $usage['input_tokens'] ?? null,
                'output_tokens' => $usage['output_tokens'] ?? null,
                'cost' => $usage ? AiSettings::cost($usage['model'], $usage['input_tokens'], $usage['output_tokens']) : null,
                'finished_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('KI-Suche nach Ereignissen fehlgeschlagen', ['search_id' => $search->id, 'error' => $e->getMessage()]);

            $search->update([
                'status' => AiEventSearch::STATUS_FAILED,
                'error' => Str::limit($e->getMessage(), 1000),
                'finished_at' => now(),
            ]);
        }

        return $search;
    }

    /**
     * Einen Lauf fuer eine hinterlegte Suche anlegen (noch nicht ausfuehren).
     */
    public function createSearchFor(AiEventSearchProfile $profile, ?int $userId = null): AiEventSearch
    {
        return AiEventSearch::create([
            'profile_id' => $profile->id,
            'status' => AiEventSearch::STATUS_RUNNING,
            'exclude_existing' => $profile->exclude_existing,
            'filters' => $profile->filtersForRun(),
            'prompt' => filled($profile->prompt) ? $profile->prompt : null,
            'started_by' => $userId,
        ]);
    }

    /**
     * Alle hinterlegten Suchen ausfuehren, deren Zeitpunkt erreicht ist –
     * nacheinander. War der Zeitplaner ausgefallen, laeuft jede Suche nur
     * einmal und danach wieder nach Plan.
     *
     * @return array{run: int, failed: int}
     */
    public function runDueProfiles(): array
    {
        $result = ['run' => 0, 'failed' => 0];

        $profiles = AiEventSearchProfile::query()
            ->where('is_active', true)
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now())
            ->orderBy('next_run_at')
            ->get();

        foreach ($profiles as $profile) {
            // Den naechsten Termin zuerst festhalten – so startet ein zweiter
            // Lauf des Zeitplaners dieselbe Suche nicht noch einmal.
            $profile->last_run_at = now();
            $profile->scheduleNext()->save();

            $search = $this->run($this->createSearchFor($profile));

            $result[$search->status === AiEventSearch::STATUS_DONE ? 'run' : 'failed']++;
        }

        return $result;
    }

    /**
     * Auftrag (frei formulierbar) plus der feste Teil: Datum, Kategorien,
     * Ausschlussliste und das Antwortformat.
     */
    public function buildPrompt(bool $excludeExisting, ?array $filters = null, ?string $prompt = null): string
    {
        $types = EventType::active()->get(['code', 'name'])
            ->map(fn (EventType $type) => '- '.$type->code.': '.$type->name)
            ->implode("\n");

        $lines = [
            // Der Auftrag der hinterlegten Suche, sonst der Standard-Auftrag.
            filled($prompt) ? trim($prompt) : AiSettings::eventSearchPrompt(),
            '',
            'Heute ist der '.now()->format('d.m.Y').'.',
            '',
            'WICHTIG – jedes Ereignis nur einmal: Berichten mehrere Quellen über dasselbe Ereignis, fasse sie zu EINEM Eintrag zusammen und nenne alle Quellen dort. Verschiedene Orte desselben Ereignisses (z. B. ein Sturm über mehreren Ländern) sind ebenfalls EIN Eintrag mit mehreren Ländern.',
            '',
            'Ordne jedes Ereignis ein:',
            '- priority: "high" (akute Gefahr oder massive Ausfälle), "medium" (deutliche Einschränkungen), "low" (geringe Einschränkungen), "info" (reiner Hinweis)',
            '- start_date / end_date: Zeitraum der Auswirkungen (JJJJ-MM-TT); end_date = null, wenn kein Ende absehbar ist',
            '- event_types: ein oder mehrere dieser Codes:',
            $types,
            '- countries: betroffene Länder als ISO-3166-Alpha-2-Codes (z. B. "IT")',
            '- location: Stadt oder Region, falls eingrenzbar, sonst null',
        ];

        // Gezielte Suche: die Filter der Ereignisliste grenzen den Auftrag ein.
        if (! empty($filters['labels'])) {
            $lines[] = '';
            $lines[] = 'EINGRENZUNG für diese Suche – sie hat Vorrang vor dem Auftrag oben. Liefere nur Ereignisse, die zu ALLEN Punkten passen:';

            foreach ($filters['labels'] as $entry) {
                $lines[] = '- '.$entry['label'].': '.$entry['value'];
            }

            if (! empty($filters['from']) || ! empty($filters['to'])) {
                $lines[] = 'Der genannte Zeitraum meint den Zeitraum der Auswirkungen – auch angekündigte, künftige Ereignisse darin zählen.';
            }
        }

        if ($excludeExisting) {
            $known = $this->knownTopics();

            if ($known->isNotEmpty()) {
                $lines[] = '';
                $lines[] = 'Diese Ereignisse sind bereits erfasst oder vorgeschlagen – lass sie weg, auch wenn du neue Quellen dazu findest:';
                $lines[] = $known->map(fn (array $topic) => '- '.$topic['title'].($topic['countries'] !== [] ? ' ('.implode(', ', $topic['countries']).')' : ''))->implode("\n");
            }
        }

        $lines[] = '';
        $lines[] = 'Liefere höchstens '.self::MAX_RESULTS.' Ereignisse, die wichtigsten zuerst. Titel und Zusammenfassung auf Deutsch; die Zusammenfassung in 2 bis 4 sachlichen Sätzen: was, wo, seit/bis wann, was bedeutet es für Reisende.';
        $lines[] = '';
        $lines[] = 'Antworte AUSSCHLIESSLICH mit JSON in genau dieser Form, ohne weiteren Text:';
        $lines[] = '{"events":[{"title":"…","summary":"…","priority":"high|medium|low|info","start_date":"JJJJ-MM-TT","end_date":"JJJJ-MM-TT oder null","event_types":["code"],"countries":["IT"],"location":"… oder null","sources":[{"title":"Name der Quelle","url":"https://…"}]}]}';

        return implode("\n", $lines);
    }

    /**
     * Was es schon gibt: ausgelieferte und heute erfasste Ereignisse sowie die
     * Vorschlaege der letzten Tage.
     *
     * @return Collection<int, array{title: string, countries: array<int, string>, start: ?string, end: ?string, event: bool}>
     */
    protected function knownTopics(): Collection
    {
        return $this->knownTopics ??= $this->loadKnownTopics();
    }

    protected function loadKnownTopics(): Collection
    {
        $events = CustomEvent::query()
            ->whereNull('superseded_by_id')
            ->where(fn ($query) => EventState::applyActive($query)->orWhere(fn ($today) => $today
                ->whereNull('superseded_by_id')
                ->whereDate('created_at', '>=', today()->subDays(2))))
            ->with(['countries', 'eventTypes:id,code'])
            ->latest('start_date')
            ->limit(250)
            ->get()
            ->map(fn (CustomEvent $event) => [
                'title' => (string) $event->getTitle('de'),
                'countries' => $event->countries->pluck('iso_code')->filter()->map(fn ($code) => strtoupper($code))->unique()->values()->all(),
                'start' => $event->start_date?->format('Y-m-d'),
                'end' => $event->end_date?->format('Y-m-d'),
                // Standorte in Worten, z. B. "Italien – Latium – Rom".
                'locations' => array_values(array_filter([rescue(fn () => $event->locationSummary('de'), null, false)])),
                'types' => $event->eventTypes->pluck('code')->all(),
                'event' => true,
            ]);

        return $events->concat($this->recentSuggestions()->map(fn (AiEventSuggestion $suggestion) => $this->topicOf($suggestion) + ['event' => false]))->filter(fn (array $topic) => $topic['title'] !== '')->values();
    }

    protected function recentSuggestions(): Collection
    {
        return AiEventSuggestion::query()
            ->where('created_at', '>=', now()->subDays(self::REMEMBER_SUGGESTIONS_DAYS))
            ->latest('id')
            ->get();
    }

    /**
     * Die Antwort der KI in gepruefter Form. Was nicht ins erwartete Format
     * passt (unbekannter Typ, ungueltiges Datum, Adresse ohne http), faellt weg.
     *
     * @return array<int, array<string, mixed>>
     */
    public function parse(string $answer): array
    {
        // Das Modell rahmt JSON gelegentlich mit ```json … ``` ein.
        $data = preg_match('/\{.*\}/s', $answer, $match) ? json_decode($match[0], true) : null;

        if (! is_array($data) || ! is_array($data['events'] ?? null)) {
            throw new \RuntimeException('Die Antwort der KI ließ sich nicht auswerten.');
        }

        $typeCodes = EventType::active()->pluck('code')->all();
        $priorities = array_keys(CustomEvent::getPriorityOptions());
        $knownCountries = Country::query()->pluck('iso_code')->filter()->map(fn ($code) => strtoupper($code))->flip();

        $date = function ($value): ?string {
            if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return null;
            }

            return rescue(fn () => Carbon::createFromFormat('Y-m-d', $value)->format('Y-m-d'), null, false);
        };

        $events = [];

        foreach ($data['events'] as $event) {
            if (! is_array($event) || blank($event['title'] ?? null)) {
                continue;
            }

            $start = $date($event['start_date'] ?? null);
            $end = $date($event['end_date'] ?? null);

            $events[] = [
                'title' => Str::limit(trim((string) $event['title']), 250, ''),
                'summary' => trim((string) ($event['summary'] ?? '')),
                'priority' => in_array($event['priority'] ?? null, $priorities, true) ? $event['priority'] : 'medium',
                'start_date' => $start,
                'end_date' => $end && $start && $end < $start ? null : $end,
                'event_type_codes' => array_values(array_unique(array_intersect(
                    array_map('strval', is_array($event['event_types'] ?? null) ? $event['event_types'] : []),
                    $typeCodes,
                ))),
                'country_codes' => collect(is_array($event['countries'] ?? null) ? $event['countries'] : [])
                    ->map(fn ($code) => strtoupper(trim((string) $code)))
                    ->filter(fn (string $code) => $knownCountries->has($code))
                    ->unique()
                    ->values()
                    ->all(),
                'location' => filled($event['location'] ?? null) ? Str::limit(trim((string) $event['location']), 250, '') : null,
                'sources' => collect(is_array($event['sources'] ?? null) ? $event['sources'] : [])
                    ->filter(fn ($source) => is_array($source) && preg_match('#^https?://#i', (string) ($source['url'] ?? '')))
                    ->map(fn (array $source) => [
                        'title' => Str::limit(trim((string) ($source['title'] ?? '')) ?: (string) parse_url($source['url'], PHP_URL_HOST), 200, ''),
                        'url' => trim((string) $source['url']),
                    ])
                    ->unique('url')
                    ->values()
                    ->all(),
            ];
        }

        return $events;
    }

    /**
     * Die gefundenen Themen als Vorschlaege speichern – jedes Ereignis nur einmal.
     *
     * @param  array<int, array<string, mixed>>  $found
     * @return int Zahl der neuen Vorschlaege
     */
    protected function store(AiEventSearch $search, array $found): int
    {
        $known = $search->exclude_existing ? $this->knownTopics()->where('event', true) : collect();
        $earlier = $this->recentSuggestions();
        $created = collect();

        foreach ($found as $event) {
            // Schon als Ereignis erfasst?
            if ($known->contains(fn (array $topic) => $this->sameTopic($event, $topic))) {
                continue;
            }

            // In dieser Antwort doppelt (andere Quelle, anderer Wortlaut): Quellen zusammenfuehren.
            $twin = $created->first(fn (AiEventSuggestion $suggestion) => $this->sameTopic($event, $this->topicOf($suggestion)));

            // Schon bei einer frueheren Suche vorgeschlagen: nicht noch einmal –
            // neue Quellen kommen an den offenen Vorschlag.
            $twin ??= $earlier->first(fn (AiEventSuggestion $suggestion) => $this->sameTopic($event, $this->topicOf($suggestion)));

            if ($twin) {
                if ($twin->status === AiEventSuggestion::STATUS_NEW) {
                    $twin->update(['sources' => collect($twin->sources ?? [])->concat($event['sources'])->unique('url')->values()->all()]);
                }

                continue;
            }

            $created->push(AiEventSuggestion::create($event + ['search_id' => $search->id]));
        }

        return $created->count();
    }

    /**
     * @return array{title: string, countries: array<int, string>, start: ?string, end: ?string, locations: array<int, string>, types: array<int, string>, urls: array<int, string>}
     */
    protected function topicOf(AiEventSuggestion $suggestion): array
    {
        return [
            'title' => $suggestion->title,
            'countries' => $suggestion->country_codes ?? [],
            'start' => $suggestion->start_date?->format('Y-m-d'),
            'end' => $suggestion->end_date?->format('Y-m-d'),
            'locations' => array_values(array_filter([$suggestion->location])),
            'types' => $suggestion->event_type_codes ?? [],
            'urls' => array_column($suggestion->sources ?? [], 'url'),
        ];
    }

    /**
     * Geht es um dasselbe Ereignis?
     *
     * Ja, wenn eine Quelle uebereinstimmt. Sonst muessen Land und Zeitraum
     * zusammenpassen und dazu entweder derselbe Ort samt Event-Typ oder ein
     * sehr aehnlicher Titel. Nennt die neue Meldung einen Ort, der beim
     * bekannten Thema nicht vorkommt, sind es verschiedene Ereignisse – lieber
     * ein Vorschlag zu viel als ein uebersehenes Ereignis.
     */
    protected function sameTopic(array $event, array $topic): bool
    {
        if (array_intersect(array_column($event['sources'] ?? [], 'url'), $topic['urls'] ?? []) !== []) {
            return true;
        }

        $eventCountries = $event['country_codes'] ?? [];
        $topicCountries = $topic['countries'] ?? [];

        // Sind auf beiden Seiten Laender bekannt, muss mindestens eines uebereinstimmen.
        if ($eventCountries !== [] && $topicCountries !== [] && array_intersect($eventCountries, $topicCountries) === []) {
            return false;
        }

        // Zeitraeume, die sich nicht beruehren, sind verschiedene Ereignisse.
        $eventStart = $event['start_date'] ?? null;
        $eventEnd = $event['end_date'] ?? null;

        if ($eventStart && ($topic['end'] ?? null) && $topic['end'] < $eventStart) {
            return false;
        }

        if ($eventEnd && ($topic['start'] ?? null) && $topic['start'] > $eventEnd) {
            return false;
        }

        $location = $this->normalize((string) ($event['location'] ?? ''));

        if ($location !== '') {
            $topicLocations = array_filter(array_map(fn ($name) => $this->normalize((string) $name), $topic['locations'] ?? []));
            // Als ganze Woerter vergleichen – "Rom" steckt sonst auch in "Strom".
            $sameLocation = collect($topicLocations)->contains(fn (string $name) => str_contains(' '.$name.' ', ' '.$location.' ') || str_contains(' '.$location.' ', ' '.$name.' '));

            // Derselbe Ort und ein gemeinsamer Event-Typ: dasselbe Ereignis, gleich wie es formuliert ist.
            $types = array_intersect($event['event_type_codes'] ?? [], $topic['types'] ?? []);

            if ($sameLocation && ($types !== [] || empty($event['event_type_codes']) || empty($topic['types']))) {
                return true;
            }

            // Der Ort kommt beim bekannten Thema gar nicht vor: ein anderes Ereignis.
            if (! $sameLocation && ! str_contains(' '.$this->normalize($topic['title']).' ', ' '.$location.' ')) {
                return false;
            }
        }

        return $this->titleSimilarity($event['title'], $topic['title']) >= self::SAME_TITLE_PERCENT;
    }

    /**
     * Aehnlichkeit zweier Titel in Prozent: Zeichenfolge oder gemeinsame
     * Woerter – je nachdem, was hoeher ausfaellt. Zusammengesetzte Woerter
     * zaehlen als Treffer ("Nahverkehrsstreik" enthaelt "Streik").
     */
    protected function titleSimilarity(string $a, string $b): float
    {
        $a = $this->normalize($a);
        $b = $this->normalize($b);

        similar_text($a, $b, $characters);

        $stopwords = ['der', 'die', 'das', 'den', 'dem', 'des', 'ein', 'eine', 'und', 'oder', 'von', 'vom', 'mit', 'bei', 'auf', 'aus', 'fur', 'zum', 'zur', 'sich', 'nach', 'wegen', 'the', 'and', 'for'];
        $tokens = fn (string $text) => array_values(array_unique(array_filter(
            explode(' ', $text),
            fn (string $word) => strlen($word) >= 3 && ! in_array($word, $stopwords, true),
        )));

        $wordsA = $tokens($a);
        $wordsB = $tokens($b);

        if ($wordsA === [] || $wordsB === []) {
            return $characters;
        }

        $matches = fn (string $word, array $others) => collect($others)->contains(
            fn (string $other) => $word === $other || (strlen($word) >= 5 && str_contains($other, $word)) || (strlen($other) >= 5 && str_contains($word, $other)),
        );

        $matched = count(array_filter($wordsA, fn ($word) => $matches($word, $wordsB)))
            + count(array_filter($wordsB, fn ($word) => $matches($word, $wordsA)));

        return max($characters, 100 * $matched / (count($wordsA) + count($wordsB)));
    }

    protected function normalize(string $title): string
    {
        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9 ]+/', ' ', Str::lower(Str::ascii($title))) ?? '') ?? '');
    }

    /**
     * Aus einem Vorschlag ein Ereignis als Entwurf anlegen – mit Titel, Text,
     * Prioritaet, Zeitraum, Event-Typen, Laendern und Quellen. Der Entwurf ist
     * nicht veroeffentlicht und laesst sich im Formular vervollstaendigen.
     */
    public function createDraft(AiEventSuggestion $suggestion, ?int $userId = null): CustomEvent
    {
        return DB::transaction(function () use ($suggestion, $userId) {
            $start = ($suggestion->start_date ?? today())->copy()->startOfDay();

            $text = collect(preg_split('/\n{2,}/', trim((string) $suggestion->summary)) ?: [])
                ->filter()
                ->map(fn (string $paragraph) => '<p>'.e(trim($paragraph)).'</p>')
                ->implode('');

            $event = new CustomEvent([
                'is_active' => false,
                'data_source' => 'manual',
                'created_by' => $userId,
            ]);

            $event->fill([
                'title_translations' => [CustomEvent::sourceLocale() => $suggestion->title],
                'popup_content_translations' => $text !== '' ? [CustomEvent::sourceLocale() => RichText::sanitize($text)] : [],
                'priority' => $suggestion->priority,
                'start_date' => $start,
                // Wie im Formular: ein Ende gilt bis zum Schluss des Tages.
                'end_date' => $suggestion->end_date?->copy()->setTime(23, 59),
                'is_nationwide' => false,
                'source_links' => collect($suggestion->sources ?? [])->map(fn (array $source) => [
                    'show_frontend' => true,
                    'link_text' => $source['title'] ?? null,
                    'link_url' => $source['url'] ?? null,
                ])->values()->all(),
                'updated_by' => $userId,
            ]);
            $event->version_internal_note = 'Aus einem KI-Vorschlag angelegt'.($suggestion->location ? ' (Ort laut KI: '.$suggestion->location.')' : '').'.';
            $event->save();

            $event->eventTypes()->sync(EventType::query()->whereIn('code', $suggestion->event_type_codes ?? [])->pluck('id')->all());

            $countryIds = Country::query()
                ->whereIn('iso_code', $suggestion->country_codes ?? [])
                ->pluck('id');

            app(CustomEventLocationService::class)->replace($event, $countryIds->map(fn ($id) => [
                'country_id' => $id,
                'use_default_coordinates' => true,
                'location_note' => $suggestion->location,
            ])->all());

            $event->unsetRelation('eventTypes');
            $event->touch();

            $suggestion->update([
                'status' => AiEventSuggestion::STATUS_CONVERTED,
                'custom_event_id' => $event->id,
                'handled_by' => $userId,
            ]);

            return $event;
        });
    }
}
