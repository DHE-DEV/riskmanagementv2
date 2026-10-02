@php
    $priorities = \App\Models\CustomEvent::getPriorityOptions();
    $priorityColors = ['high' => '#dc2626', 'medium' => '#d97706', 'low' => '#0284c7', 'info' => '#71717a'];
    $countryNames = \App\Models\Country::query()
        ->whereIn('iso_code', $suggestions->flatMap(fn ($suggestion) => $suggestion->country_codes ?? [])->unique())
        ->get()
        ->mapWithKeys(fn ($country) => [strtoupper((string) $country->iso_code) => $country->getName('de')]);
    $done = $search->status === \App\Models\AiEventSearch::STATUS_DONE;
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KI-Suche „{{ $name }}“</title>
</head>
<body style="margin: 0; padding: 24px; background: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; color: #18181b; line-height: 1.5;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e4e4e7;">
        <div style="background: #0b3250; padding: 20px 28px; color: #ffffff; font-size: 14px; font-weight: 600;">
            Passolution Travel Information Platform
        </div>

        <div style="padding: 28px;">
            <p style="margin: 0 0 6px 0; font-size: 13px; color: #71717a;">Ergebnis der KI-Suche vom {{ $search->created_at?->format('d.m.Y H:i') }}</p>
            <h1 style="margin: 0 0 16px 0; font-size: 20px; line-height: 1.3;">{{ $name }}</h1>

            @if ($done)
                <p style="margin: 0 0 16px 0;">
                    Die KI hat <strong>{{ $search->found_count }} {{ $search->found_count === 1 ? 'Thema' : 'Themen' }}</strong> gefunden,
                    davon <strong>{{ $search->new_count }} neu</strong>.
                </p>
            @else
                <p style="margin: 0 0 16px 0; color: #b91c1c;">
                    Die Suche ist fehlgeschlagen{{ $search->error ? ': '.\Illuminate\Support\Str::limit($search->error, 300) : '.' }}
                </p>
            @endif

            @if ($search->filterSummary())
                <p style="margin: 0 0 20px 0; font-size: 13px; color: #52525b;">Eingrenzung: {{ $search->filterSummary() }}</p>
            @endif

            @foreach ($suggestions as $suggestion)
                <div style="margin: 0 0 14px 0; padding: 14px 16px; border: 1px solid #e4e4e7; border-left: 4px solid {{ $priorityColors[$suggestion->priority] ?? '#71717a' }}; border-radius: 8px;">
                    <div style="font-size: 15px; font-weight: 600;">{{ $suggestion->title }}</div>
                    <div style="margin-top: 4px; font-size: 13px; color: #52525b;">
                        {{ $priorities[$suggestion->priority] ?? $suggestion->priority }}
                        · {{ $suggestion->start_date?->format('d.m.Y') ?? 'Beginn unklar' }} – {{ $suggestion->end_date?->format('d.m.Y') ?? 'offen' }}
                        @if (! empty($suggestion->country_codes))
                            · {{ collect($suggestion->country_codes)->map(fn ($code) => $countryNames[$code] ?? $code)->implode(', ') }}
                        @endif
                        @if ($suggestion->location)
                            · {{ $suggestion->location }}
                        @endif
                    </div>
                    @if ($suggestion->summary)
                        <div style="margin-top: 8px; font-size: 14px; color: #3f3f46;">{{ \Illuminate\Support\Str::limit($suggestion->summary, 400) }}</div>
                    @endif
                </div>
            @endforeach

            <a href="{{ $url }}" style="display: inline-block; margin-top: 8px; background: #0b3250; color: #ffffff; text-decoration: none; padding: 11px 22px; border-radius: 8px; font-weight: 600; font-size: 14px;">
                Suchergebnis öffnen
            </a>

            <p style="margin: 20px 0 0 0; font-size: 12px; color: #71717a;">
                Sie erhalten diese Mail, weil Sie bei der hinterlegten KI-Suche „{{ $name }}“ als Empfänger eingetragen sind (Admin-Bereich › System › KI).
            </p>
        </div>
    </div>
</body>
</html>
