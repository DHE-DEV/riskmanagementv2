{{--
    Suche mit Autovervollstaendigung und Seitengroesse fuer eine Liste der
    Seitenspalte (Country-Editor: relatedSearch / relatedLimit je Liste).

    - list: Schluessel der Liste (regions, cities, airports)
    - data: der Eintrag aus $this->related (suggestions, limit, …)
    - placeholder: Text im Suchfeld
--}}
@props([
    'list',
    'data',
    'placeholder' => 'Suchen …',
    'limits' => ['15', '30', '100', 'all'],
])

<div class="flex flex-wrap items-center gap-2">
    <div class="min-w-0 flex-1">
        <flux:input
            wire:model.live.debounce.300ms="relatedSearch.{{ $list }}"
            icon="magnifying-glass"
            size="sm"
            :placeholder="$placeholder"
            aria-label="{{ $placeholder }}"
            list="related-suggest-{{ $list }}"
            autocomplete="off"
            clearable
        />
        <datalist id="related-suggest-{{ $list }}">
            @foreach ($data['suggestions'] as $suggestion)
                <option value="{{ $suggestion }}"></option>
            @endforeach
        </datalist>
    </div>
    <div class="w-24 shrink-0">
        <flux:select wire:model.live="relatedLimit.{{ $list }}" size="sm" aria-label="Einträge je Seite">
            @foreach ($limits as $limit)
                <flux:select.option value="{{ $limit }}">{{ $limit === 'all' ? 'Alle' : $limit }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>
</div>
