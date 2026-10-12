@php
    $done = $run->status === \App\Models\RegionInfoRun::STATUS_DONE;
    $countries = implode(', ', (array) ($result['countries'] ?? [])) ?: 'alle Länder';
    $unchanged = \App\Jobs\FillMissingRegionInfoJob::unchanged($run);
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KI-Ergänzung der Regionsinfos</title>
</head>
<body style="margin: 0; padding: 24px; background: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; color: #18181b; line-height: 1.5;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e4e4e7;">
        <div style="background: #0b3250; padding: 20px 28px; color: #ffffff; font-size: 14px; font-weight: 600;">
            Passolution Travel Information Platform
        </div>

        <div style="padding: 28px;">
            <p style="margin: 0 0 6px 0; font-size: 13px; color: #71717a;">
                Lauf #{{ $run->id }} · gestartet {{ $run->created_at?->timezone(config('app.timezone'))->format('d.m.Y H:i') }}
                @if ($run->finished_at)
                    · beendet {{ $run->finished_at->timezone(config('app.timezone'))->format('d.m.Y H:i') }}
                @endif
            </p>
            <h1 style="margin: 0 0 16px 0; font-size: 20px; line-height: 1.3;">KI-Ergänzung der Regionsinfos: {{ $countries }}</h1>

            @unless ($done)
                <p style="margin: 0 0 16px 0; color: #b91c1c;">Der Lauf wurde abgebrochen{{ $run->error ? ': '.\Illuminate\Support\Str::limit($run->error, 300) : '.' }}</p>
            @endunless

            <table style="width: 100%; border-collapse: collapse; font-size: 14px; margin: 0 0 20px 0;">
                <tr><td style="padding: 6px 0; color: #52525b;">Regionen / Teilgebiete mit Lücken</td><td style="padding: 6px 0; text-align: right;">{{ (int) ($result['regions'] ?? 0) }} / {{ (int) ($result['subregions'] ?? 0) }}</td></tr>
                <tr><td style="padding: 6px 0; color: #52525b;">Regionen nach neuen Teilgebieten durchsucht</td><td style="padding: 6px 0; text-align: right;">{{ (int) ($result['discover'] ?? 0) }}</td></tr>
                <tr><td style="padding: 6px 0; color: #52525b;">Teilgebiete neu angelegt</td><td style="padding: 6px 0; text-align: right; font-weight: 600;">{{ $created->count() }}</td></tr>
                <tr><td style="padding: 6px 0; color: #52525b;">Ergänzt</td><td style="padding: 6px 0; text-align: right; font-weight: 600;">{{ (int) ($result['changed'] ?? 0) }}</td></tr>
                <tr><td style="padding: 6px 0; color: #52525b;">Ohne neue Angaben (KI wusste nichts Sicheres)</td><td style="padding: 6px 0; text-align: right;">{{ $unchanged }}</td></tr>
                <tr><td style="padding: 6px 0; color: #52525b;">Fehlgeschlagen</td><td style="padding: 6px 0; text-align: right;">{{ $failures->count() }}</td></tr>
                <tr><td style="padding: 6px 0; color: #52525b;">Websuche</td><td style="padding: 6px 0; text-align: right;">{{ ($result['web'] ?? false) ? 'ja' : 'nein' }}</td></tr>
                <tr><td style="padding: 6px 0; color: #52525b;">Tokens</td><td style="padding: 6px 0; text-align: right;">{{ number_format((int) ($result['tokens'] ?? 0), 0, ',', '.') }}</td></tr>
            </table>

            @if ($created->isNotEmpty())
                <p style="margin: 0 0 8px 0; font-weight: 600;">Neue Teilgebiete</p>
                <ul style="margin: 0 0 20px 0; padding-left: 20px; font-size: 13px; color: #3f3f46;">
                    @foreach ($created->take(80) as $subregion)
                        <li>{{ $subregion->getName('de') }} ({{ $subregion->kindLabel() }}, {{ $subregion->region?->getName('de') }}){{ $subregion->cities_count ? ' · '.$subregion->cities_count.' '.($subregion->cities_count === 1 ? 'Stadt' : 'Städte').' zugeordnet' : '' }}</li>
                    @endforeach
                    @if ($created->count() > 80)
                        <li>… und {{ $created->count() - 80 }} weitere</li>
                    @endif
                </ul>
            @endif

            @if ($failures->isNotEmpty())
                <p style="margin: 0 0 8px 0; font-weight: 600;">Fehlgeschlagen</p>
                <ul style="margin: 0 0 20px 0; padding-left: 20px; font-size: 13px; color: #3f3f46;">
                    @foreach ($failures->take(50) as $failure)
                        <li>{{ $failure['name'] }}: {{ \Illuminate\Support\Str::limit($failure['message'], 160) }}</li>
                    @endforeach
                    @if ($failures->count() > 50)
                        <li>… und {{ $failures->count() - 50 }} weitere</li>
                    @endif
                </ul>
            @endif

            <p style="margin: 0 0 16px 0; font-size: 14px;">Alles Ergänzte und neu Angelegte steht als „KI-Entwurf, ungeprüft“ in der Admin v2 – im Editor ist vermerkt, welche Felder neu sind und welche Teilgebiete die KI angelegt hat.</p>

            <a href="{{ $regionsUrl }}" style="display: inline-block; margin: 0 8px 8px 0; background: #0b3250; color: #ffffff; text-decoration: none; padding: 11px 22px; border-radius: 8px; font-weight: 600; font-size: 14px;">Regionen prüfen</a>
            <a href="{{ $subregionsUrl }}" style="display: inline-block; margin: 0 0 8px 0; background: #ffffff; color: #0b3250; text-decoration: none; padding: 10px 21px; border-radius: 8px; font-weight: 600; font-size: 14px; border: 1px solid #0b3250;">Teilgebiete prüfen</a>

            <p style="margin: 20px 0 0 0; font-size: 12px; color: #71717a;">
                Sie erhalten diese Mail, weil Ihre Adresse beim Start von <code>regions:fill-missing</code> angegeben wurde.
            </p>
        </div>
    </div>
</body>
</html>
