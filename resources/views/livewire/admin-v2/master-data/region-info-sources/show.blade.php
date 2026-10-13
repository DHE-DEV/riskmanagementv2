@php
    use App\Livewire\AdminV2\MasterData\RegionInfoSources\Show;

    $groups = $this->groups;
    $total = $groups->flatten()->count();
@endphp

<div class="flex flex-col gap-6">
    <div>
        <a href="{{ $this->editUrl() }}" class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white">
            <flux:icon.arrow-left variant="micro" /> Zurück zu {{ $name }}
        </a>
        <flux:heading size="xl" level="1" class="mt-1">Quellen der KI-Recherche – {{ $name }}</flux:heading>
        <flux:subheading class="mt-1">Seiten, auf die sich die KI bei der Websuche gestützt hat – zum Gegenprüfen der Angaben. Die Texte sind eigenständig formuliert; die Quellen liefern nur die Fakten.</flux:subheading>
    </div>

    @if ($total === 0)
        <x-adminv2.card>
            <p class="text-sm text-zinc-500">Keine Quellen gespeichert. Quellen gibt es nur für Angaben, die die KI mit Websuche recherchiert hat.</p>
        </x-adminv2.card>
    @else
        @foreach ($groups as $field => $sources)
            <x-adminv2.card wire:key="field-{{ $field ?: 'visited' }}" :heading="Show::fieldLabel((string) $field)" :description="$field === '' ? 'Von der Websuche geöffnet, aber nicht als Beleg angegeben – nicht unbedingt verwendet.' : $sources->count().' '.($sources->count() === 1 ? 'Quelle' : 'Quellen')">
                <ul class="flex flex-col divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                    @foreach ($sources as $source)
                        <li wire:key="source-{{ $source->id }}" class="flex flex-wrap items-baseline gap-x-3 gap-y-0.5 py-2">
                            <a href="{{ $source->url }}" target="_blank" rel="noopener noreferrer nofollow" class="min-w-0 break-words text-zinc-900 underline decoration-zinc-300 underline-offset-2 hover:decoration-zinc-900 dark:text-white">{{ $source->title ?: $source->url }}</a>
                            <span class="text-xs text-zinc-500">{{ $source->domain }}</span>
                            @if ($source->cited && $field === '')
                                <flux:badge size="sm" color="zinc">belegt</flux:badge>
                            @endif
                            <span class="ml-auto text-xs text-zinc-500 tabular-nums">{{ $source->created_at?->timezone(config('app.timezone'))->format('d.m.Y') }}@if ($source->region_info_run_id) · Lauf #{{ $source->region_info_run_id }}@endif</span>
                        </li>
                    @endforeach
                </ul>
            </x-adminv2.card>
        @endforeach
    @endif
</div>
