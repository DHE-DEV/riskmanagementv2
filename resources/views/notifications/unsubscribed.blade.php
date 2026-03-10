<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erfolgreich abgemeldet</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f3f4f6; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .card { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 2rem; max-width: 480px; width: 100%; text-align: center; }
        h1 { font-size: 1.25rem; color: #111827; margin-bottom: 1rem; }
        p { color: #6b7280; margin-bottom: 1.5rem; line-height: 1.6; }
        .checkmark { font-size: 3rem; margin-bottom: 1rem; color: #16a34a; }
        .btn { display: inline-block; padding: 0.625rem 1.5rem; border-radius: 6px; font-size: 0.875rem; font-weight: 500; cursor: pointer; border: none; text-decoration: none; background: #e5e7eb; color: #374151; }
        .btn:hover { background: #d1d5db; }
    </style>
</head>
<body>
    <div class="card">
        <div class="checkmark">&#10003;</div>

        @if(!empty($alreadyUnsubscribed))
            <h1>Bereits abgemeldet</h1>
            <p>Sie wurden bereits von diesen Benachrichtigungen abgemeldet.</p>
        @else
            <h1>Erfolgreich abgemeldet</h1>
            <p>Sie wurden erfolgreich von den Benachrichtigungen abgemeldet.</p>
        @endif

        <a href="{{ url('/') }}" class="btn">Zur Startseite</a>
    </div>
</body>
</html>
