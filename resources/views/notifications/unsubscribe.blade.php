<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benachrichtigungen abmelden</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f3f4f6; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .card { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 2rem; max-width: 480px; width: 100%; text-align: center; }
        h1 { font-size: 1.25rem; color: #111827; margin-bottom: 1rem; }
        p { color: #6b7280; margin-bottom: 1.5rem; line-height: 1.6; }
        .rule-name { font-weight: 600; color: #111827; }
        .btn { display: inline-block; padding: 0.625rem 1.5rem; border-radius: 6px; font-size: 0.875rem; font-weight: 500; cursor: pointer; border: none; text-decoration: none; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-secondary { background: #e5e7eb; color: #374151; margin-left: 0.5rem; }
        .btn-secondary:hover { background: #d1d5db; }
        .actions { display: flex; justify-content: center; gap: 0.75rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Benachrichtigungen abmelden</h1>

        @if($ruleName)
            <p>
                Sie sind dabei, die Benachrichtigungsregel
                <span class="rule-name">"{{ $ruleName }}"</span>
                zu deaktivieren. Sie erhalten danach keine E-Mails mehr von dieser Regel.
            </p>
        @else
            <p>
                Sie sind dabei, sich von <strong>allen Benachrichtigungen</strong> abzumelden.
                Sie erhalten danach keine E-Mail-Benachrichtigungen mehr.
            </p>
        @endif

        <div class="actions">
            <form method="POST" action="{{ url('/notifications/unsubscribe/' . $token->token) }}">
                @csrf
                <button type="submit" class="btn btn-danger">Abmelden</button>
            </form>
            <a href="{{ url('/') }}" class="btn btn-secondary">Abbrechen</a>
        </div>
    </div>
</body>
</html>
