<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $invite ? ($invite['kind'] === 'trip' ? 'Einladung zur Reise „'.$invite['name'].'“' : 'Einladung in die Gruppe „'.$invite['name'].'“') : 'Einladung' }} – TripData</title>
    <meta name="robots" content="noindex">
    @if ($invite)
        <meta property="og:title" content="{{ $invite['from'] }} lädt dich ein – TripData">
        <meta property="og:description" content="{{ $invite['kind'] === 'trip' ? 'Reise „'.$invite['name'].'“' : 'Gruppe „'.$invite['name'].'“' }} in der App TripData von Passolution.">
    @endif
    <style>
        :root { color-scheme: light dark; --bg: #f4f5f7; --card: #fff; --text: #111827; --muted: #6b7280; --accent: #2563eb; }
        @media (prefers-color-scheme: dark) { :root { --bg: #0b0f17; --card: #161b26; --text: #f3f4f6; --muted: #9ca3af; --accent: #60a5fa; } }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: var(--bg); color: var(--text); }
        main { max-width: 440px; margin: 0 auto; padding: 32px 16px 48px; }
        .card { background: var(--card); border-radius: 20px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
        .app { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
        .icon { width: 52px; height: 52px; border-radius: 12px; background: var(--accent); color: #fff; display: grid; place-items: center; font-size: 26px; }
        h1 { font-size: 22px; margin: 0 0 6px; line-height: 1.25; }
        p { margin: 6px 0; color: var(--muted); line-height: 1.45; }
        .meta { margin: 14px 0; padding: 12px 14px; border-radius: 12px; background: var(--bg); color: var(--text); font-size: 15px; }
        .btn { display: block; text-align: center; text-decoration: none; padding: 14px; border-radius: 12px; font-weight: 600; margin-top: 10px; }
        .primary { background: var(--accent); color: #fff; }
        .secondary { background: var(--bg); color: var(--accent); }
        .code { font: 600 26px ui-monospace, SFMono-Regular, Menlo, monospace; letter-spacing: 2px; text-align: center; padding: 12px; border: 2px dashed var(--muted); border-radius: 12px; margin: 10px 0; color: var(--text); }
        ol { color: var(--muted); padding-left: 20px; line-height: 1.6; }
        small { color: var(--muted); }
    </style>
</head>
<body>
<main>
    <div class="card">
        <div class="app">
            <div class="icon">✈︎</div>
            <div><strong>TripData</strong><br><small>Reise-App von Passolution</small></div>
        </div>
        @if ($invite)
            <h1>
                @if ($invite['kind'] === 'trip')
                    {{ $invite['from'] }} möchte die Reise „{{ $invite['name'] }}“ mit dir teilen
                @else
                    {{ $invite['from'] }} lädt dich in die Gruppe „{{ $invite['name'] }}“ ein
                @endif
            </h1>
            @if ($invite['kind'] === 'trip' && ($invite['start_date'] || $invite['country_codes']))
                <div class="meta">
                    @if ($invite['start_date'])
                        {{ \Illuminate\Support\Carbon::parse($invite['start_date'])->locale('de')->isoFormat('D. MMMM YYYY') }}@if ($invite['end_date'] && $invite['end_date'] !== $invite['start_date']) – {{ \Illuminate\Support\Carbon::parse($invite['end_date'])->locale('de')->isoFormat('D. MMMM YYYY') }}@endif
                        <br>
                    @endif
                    @if ($invite['country_codes'])
                        {{ collect($invite['country_codes'])->map(fn ($c) => \Locale::getDisplayRegion('-'.$c, 'de'))->join(', ') }}
                    @endif
                </div>
            @elseif ($invite['kind'] === 'group' && $invite['members'])
                <div class="meta">{{ $invite['members'] }} {{ $invite['members'] === 1 ? 'Mitglied' : 'Mitglieder' }}</div>
            @endif

            <a class="btn primary" href="{{ $appUrl }}">In TripData öffnen</a>
            <p style="margin-top:20px"><strong style="color:var(--text)">Noch keine App?</strong> So geht's:</p>
            <ol>
                <li>TripData installieren:</li>
            </ol>
            <a class="btn secondary" href="{{ $appStoreUrl }}">Im App Store laden (iPhone)</a>
            <a class="btn secondary" href="{{ $playStoreUrl }}">Bei Google Play laden (Android)</a>
            <ol start="2">
                <li>Diesen Link noch einmal öffnen – oder in der App unter <em>Mehr › Teilen &amp; Gruppen › Einladungscode eingeben</em> diesen Code eintragen:</li>
            </ol>
            <div class="code">{{ $invite['code'] }}</div>
            <small>Gültig bis {{ \Illuminate\Support\Carbon::parse($invite['expires_at'])->locale('de')->isoFormat('D. MMMM YYYY') }}.</small>
        @else
            <h1>Diese Einladung ist abgelaufen oder ungültig</h1>
            <p>Bitte die Person, die dich eingeladen hat, um einen neuen Link.</p>
            <a class="btn secondary" href="{{ $appStoreUrl }}">TripData im App Store</a>
            <a class="btn secondary" href="{{ $playStoreUrl }}">TripData bei Google Play</a>
        @endif
    </div>
</main>
</body>
</html>
