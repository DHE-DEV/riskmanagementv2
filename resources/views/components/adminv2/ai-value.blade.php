{{--
    Wert aus einer KI-Pruefung (Vorschlag, bisheriger Wert). Mehrere Angaben –
    je Zeile eine, z. B. bei "Gepäckregeln (alle Angaben)" – stehen als Liste
    untereinander, die Bezeichnung vor dem Doppelpunkt hervorgehoben.

    - text: der Wert
    - prefix: Vorspann der ersten Zeile, z. B. "Bisher:"
--}}
@props([
    'text',
    'prefix' => null,
])

@php
    $lines = collect(preg_split('/\R/u', trim((string) $text)) ?: [])
        ->map(fn ($line) => trim(preg_replace('/^\s*(?:[-*•–]\s+|\d+[.)]\s+)/u', '', $line)))
        ->filter(fn ($line) => $line !== '')
        ->values();

    // Eine einzige lange Zeile mit Strichpunkten ist eine Aufzaehlung.
    if ($lines->count() === 1 && mb_strlen($lines[0]) > 120 && str_contains($lines[0], '; ')) {
        $lines = collect(explode('; ', $lines[0]))->map(fn ($line) => trim($line))->filter()->values();
    }

    // "Bezeichnung: Wert" – aber keine Adressen wie "https://…" und keine Uhrzeiten.
    $split = fn (string $line) => preg_match('/^([^:\/]{1,40}):\s+(\S.*)$/u', $line, $match) ? [$match[1], $match[2]] : [null, $line];
@endphp

@if ($lines->count() <= 1)
    <span {{ $attributes->class('block break-words') }}>@if ($prefix){{ $prefix }} @endif{{ $lines->first() ?? '–' }}</span>
@else
    <div {{ $attributes->class('break-words') }}>
        @if ($prefix) <span class="block">{{ $prefix }}</span> @endif
        <ul class="mt-0.5 flex flex-col gap-1">
            @foreach ($lines as $line)
                @php [$label, $rest] = $split($line); @endphp
                <li class="flex gap-1.5">
                    <span class="select-none text-zinc-400" aria-hidden="true">•</span>
                    <span class="min-w-0">@if ($label)<span class="font-semibold">{{ $label }}:</span> @endif<span class="font-normal">{{ $rest }}</span></span>
                </li>
            @endforeach
        </ul>
    </div>
@endif
