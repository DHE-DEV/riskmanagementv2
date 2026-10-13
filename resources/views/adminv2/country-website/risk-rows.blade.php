{{-- Felder einer Kategorie des Risikoprofils: Stufe mit Farbpunkt, Ja/Nein, Schlagworte, Text – jeweils mit Notiz. --}}
@php
    $dot = fn (?int $level) => match ($level) { 1 => 'bg-emerald-500', 2 => 'bg-lime-500', 3 => 'bg-amber-500', 4 => 'bg-orange-600', 5 => 'bg-red-600', default => 'bg-zinc-400' };
@endphp
<dl class="divide-y divide-zinc-100 dark:divide-zinc-800">
    @foreach ($entries as $entry)
        <div class="py-3">
            <div class="flex items-start justify-between gap-4">
                <dt class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $entry['name'] }}</dt>
                <dd class="shrink-0 text-end text-sm">
                    @switch($entry['type'])
                        @case('level')
                            @if ($entry['value'])<span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2.5 py-0.5 font-semibold dark:bg-zinc-800"><span class="size-2 rounded-full {{ $dot($entry['value']) }}"></span>{{ $entry['label'] ?? $entry['value'] }}</span>@endif
                            @break
                        @case('bool')
                            <span class="font-semibold {{ $entry['value'] ? 'text-emerald-600' : 'text-zinc-500' }}">{{ $entry['value'] ? 'Ja' : 'Nein' }}</span>
                            @break
                        @case('tags')
                            <span class="flex flex-wrap justify-end gap-1">@foreach ($entry['value'] as $tag)<span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs dark:bg-zinc-800">{{ is_array($tag) ? ($tag['name'] ?? $tag['label'] ?? '') : $tag }}</span>@endforeach</span>
                            @break
                        @default
                            @if (mb_strlen((string) $entry['value']) <= 40)<span class="font-semibold">{{ $entry['value'] }}</span>@endif
                    @endswitch
                </dd>
            </div>
            @if (($entry['type'] ?? '') === 'text' && mb_strlen((string) $entry['value']) > 40)
                <p class="mt-1 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">{{ $entry['value'] }}</p>
            @endif
            @if (! empty($entry['note']))
                <p class="mt-1 text-sm leading-relaxed text-zinc-500">{{ $entry['note'] }}</p>
            @endif
        </div>
    @endforeach
</dl>
