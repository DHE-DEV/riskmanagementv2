{{--
    Blaettern in einer Liste der Seitenspalte: "1–15 von 51" und Vor/Zurueck
    (Country-Editor: relatedGoto).

    - list: Schluessel der Liste
    - data: der Eintrag aus $this->related (page, last_page, from, to, found)
--}}
@props([
    'list',
    'data',
])

<div class="flex flex-wrap items-center justify-between gap-2 text-xs text-zinc-500 dark:text-zinc-400">
    @php
        $n = fn (int $value) => number_format($value, 0, ',', '.');
        $range = $n($data['from']).'–'.$n($data['to']).' von '.$n($data['found']).($data['search'] !== '' && $data['found'] !== $data['count'] ? ' Treffern' : '');
    @endphp
    <span class="tabular-nums">{{ $range }}</span>

    @if ($data['last_page'] > 1)
        <div class="flex items-center gap-1">
            <flux:button size="xs" variant="ghost" icon="chevron-left" wire:click="relatedGoto('{{ $list }}', {{ $data['page'] - 1 }})" :disabled="$data['page'] <= 1" aria-label="Vorherige Seite" />
            <span class="tabular-nums">{{ $data['page'] }} / {{ $data['last_page'] }}</span>
            <flux:button size="xs" variant="ghost" icon="chevron-right" wire:click="relatedGoto('{{ $list }}', {{ $data['page'] + 1 }})" :disabled="$data['page'] >= $data['last_page']" aria-label="Nächste Seite" />
        </div>
    @endif
</div>
