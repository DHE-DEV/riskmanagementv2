@php
    use App\Jobs\FillMissingRegionInfoJob;
    use App\Models\RegionInfoRun;

    $country = $this->country;
    $run = $this->run;
    $running = (bool) $run?->isRunning();
    $result = (array) ($run?->result ?? []);
    $failures = $this->failures;
    $processed = $run ? $run->done + $failures->count() : 0;
    $percent = $run && $run->total > 0 ? min(100, (int) floor($processed / $run->total * 100)) : 0;
    $number = fn ($value) => number_format((int) $value, 0, ',', '.');
@endphp

<div>
    <x-adminv2.card
        heading="KI-Lauf"
        :description="$country ? 'Fehlende Regionsinfos aller Regionen und Teilgebiete von '.$country->getName('de').' per KI ergänzen – im Hintergrund, mit Mail am Ende.' : 'Das Land dieser Aufgabe gibt es nicht mehr.'"
    >
        @if ($running)
            <x-slot:actions>
                <flux:button size="sm" variant="ghost" icon="stop" wire:click="cancel" wire:confirm="Lauf anhalten? Was gerade in Arbeit ist, wird noch fertig.">Anhalten</flux:button>
            </x-slot:actions>
        @endif

        @if ($country)
            <div class="flex flex-col gap-5 text-sm" @if ($running) wire:poll.5s="reload" @endif>
                @if ($run)
                    <div class="flex flex-col gap-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-medium text-zinc-900 dark:text-white">Lauf #{{ $run->id }}</span>
                            <flux:badge size="sm" :color="match ($run->status) { RegionInfoRun::STATUS_RUNNING => 'sky', RegionInfoRun::STATUS_DONE => 'green', RegionInfoRun::STATUS_CANCELLED => 'zinc', default => 'red' }">
                                {{ match ($run->status) { RegionInfoRun::STATUS_RUNNING => 'läuft', RegionInfoRun::STATUS_DONE => 'fertig', RegionInfoRun::STATUS_CANCELLED => 'angehalten', default => 'abgebrochen' } }}
                            </flux:badge>
                            @if (($result['task_id'] ?? null) !== $taskId)
                                <span class="text-xs text-zinc-500">Sammellauf für {{ implode(', ', (array) ($result['countries'] ?? [])) }}</span>
                            @endif
                            <span class="text-xs text-zinc-500 tabular-nums">
                                gestartet {{ $run->created_at?->format('d.m.Y H:i') }}@if ($run->finished_at), beendet {{ $run->finished_at->format('d.m.Y H:i') }}@endif
                            </span>
                        </div>

                        <div class="h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $run->total }}" aria-valuenow="{{ $processed }}">
                            <div @class(['h-full rounded-full transition-all', 'bg-emerald-600' => $run->status !== RegionInfoRun::STATUS_FAILED, 'bg-red-500' => $run->status === RegionInfoRun::STATUS_FAILED]) style="width: {{ $percent }}%"></div>
                        </div>

                        <dl class="grid grid-cols-2 gap-x-4 gap-y-1 sm:grid-cols-3">
                            <div><dt class="text-xs text-zinc-500">Bearbeitet</dt><dd class="tabular-nums font-medium text-zinc-900 dark:text-white">{{ $number($processed) }} von {{ $number($run->total) }}</dd></div>
                            <div><dt class="text-xs text-zinc-500">Ergänzt</dt><dd class="tabular-nums font-medium text-zinc-900 dark:text-white">{{ $number($result['changed'] ?? 0) }}</dd></div>
                            <div><dt class="text-xs text-zinc-500">Teilgebiete neu</dt><dd class="tabular-nums font-medium text-zinc-900 dark:text-white">{{ $number($result['created'] ?? 0) }}</dd></div>
                            <div><dt class="text-xs text-zinc-500">Ohne neue Angaben</dt><dd class="tabular-nums text-zinc-700 dark:text-zinc-300">{{ $number(FillMissingRegionInfoJob::unchanged($run)) }}</dd></div>
                            <div><dt class="text-xs text-zinc-500">Fehlgeschlagen</dt><dd @class(['tabular-nums', 'font-medium text-red-600 dark:text-red-400' => $failures->isNotEmpty(), 'text-zinc-700 dark:text-zinc-300' => $failures->isEmpty()])>{{ $number($failures->count()) }}</dd></div>
                            <div><dt class="text-xs text-zinc-500">Tokens</dt><dd class="tabular-nums text-zinc-700 dark:text-zinc-300">{{ $number($result['tokens'] ?? 0) }}</dd></div>
                        </dl>

                        @if ($running)
                            <p class="text-zinc-600 dark:text-zinc-400">Läuft im Hintergrund{{ ($result['web'] ?? false) ? ' mit Websuche, je Eintrag etwa eine Minute' : '' }} – die Seite muss nicht offen bleiben.@if ($result['notify'] ?? []) Am Ende geht eine Mail an {{ implode(', ', $result['notify']) }}.@endif</p>
                        @elseif ($run->status === RegionInfoRun::STATUS_FAILED && $run->error)
                            <p class="text-red-600 dark:text-red-400">{{ $run->error }}</p>
                        @endif

                        @if ($failures->isNotEmpty())
                            <details class="text-zinc-600 dark:text-zinc-400">
                                <summary class="cursor-pointer">Fehlgeschlagene Einträge</summary>
                                <ul class="mt-2 flex flex-col gap-1">
                                    @foreach ($failures->take(30) as $failure)
                                        <li wire:key="failure-{{ $failure['key'] }}">{{ $failure['name'] }}: {{ \Illuminate\Support\Str::limit($failure['message'], 160) }}</li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif

                        @if (! $running && $run->status !== RegionInfoRun::STATUS_CANCELLED)
                            <div class="flex flex-wrap gap-2">
                                <flux:button size="sm" variant="ghost" icon="arrow-top-right-on-square" :href="route('adminv2.master-data.regions.index', ['info' => \App\Support\AdminV2\RegionInfo::STATUS_AI, 'country' => [$country->id]])" target="_blank">Regionen prüfen</flux:button>
                                <flux:button size="sm" variant="ghost" icon="arrow-top-right-on-square" :href="route('adminv2.master-data.subregions.index', ['status' => \App\Support\AdminV2\RegionInfo::STATUS_AI, 'country' => [$country->id]])" target="_blank">Teilgebiete prüfen</flux:button>
                            </div>
                        @endif
                    </div>
                @endif

                @unless ($running)
                    @php $preview = $this->preview; @endphp
                    <form wire:submit="start" @class(['flex flex-col gap-4', 'border-t border-zinc-100 pt-5 dark:border-zinc-800' => $run])>
                        <div class="rounded-xl bg-zinc-50 p-3 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                            @if ($preview['regions'] + $preview['subregions'] + $preview['discover'] === 0)
                                Keine Lücken – nichts zu ergänzen.
                            @else
                                Offen: <span class="font-medium tabular-nums">{{ $number($preview['regions']) }}</span> Regionen und <span class="font-medium tabular-nums">{{ $number($preview['subregions']) }}</span> Teilgebiete mit Lücken ({{ $number($preview['gaps']) }} Felder × Sprachen)@if ($preview['discover'])<span>, Suche nach neuen Teilgebieten in <span class="font-medium tabular-nums">{{ $number($preview['discover']) }}</span> Regionen</span>@endif.
                            @endif
                        </div>

                        <flux:checkbox wire:model.live="web" label="Mit Websuche" description="Die KI recherchiert im Internet – genauer und aktueller, aber langsamer und teurer (etwa 30.000 Tokens je Eintrag)." />
                        <flux:checkbox wire:model.live="discover" label="Neue Teilgebiete suchen" description="Je Region bekannte Reiseregionen und Inseln, die noch fehlen – sie werden angelegt und bekommen im selben Lauf ihre Infos." />
                        <flux:input wire:model="notify" label="Mail am Ende an" description="Mehrere Adressen mit Komma trennen; leer: keine Mail." placeholder="name@passolution.de" />

                        <p class="text-xs text-zinc-500">Es werden nur leere Felder und fehlende Sprachen gefüllt. Die KI schreibt alle Texte in eigenen Worten; Ergänztes steht danach als „KI-Entwurf, ungeprüft“ an den Regionen und Teilgebieten. Nach dem Lauf ist die Aufgabe erledigt.</p>

                        <div>
                            <flux:button type="submit" variant="primary" icon="sparkles" wire:loading.attr="disabled">{{ $run ? 'Neuen Lauf starten' : 'Lauf starten' }}</flux:button>
                        </div>
                    </form>
                @endunless
            </div>
        @endif
    </x-adminv2.card>
</div>
