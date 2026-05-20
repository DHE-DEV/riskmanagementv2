<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>KC Tools</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; max-width: 720px; margin: 2rem auto; padding: 0 1rem; color: #1f2937; background: #f9fafb; }
        h1 { font-size: 1.25rem; color: #b91c1c; margin-bottom: 0.25rem; }
        .hint { font-size: 0.85rem; color: #6b7280; margin-bottom: 2rem; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 1px 2px rgba(0,0,0,0.04); }
        h2 { font-size: 1rem; margin: 0 0 1rem; }
        label { display: block; font-size: 0.85rem; font-weight: 500; margin-bottom: 0.25rem; }
        input { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.9rem; margin-bottom: 0.75rem; }
        button { background: #1f2937; color: #fff; border: 0; padding: 0.55rem 1.1rem; border-radius: 6px; font-size: 0.9rem; cursor: pointer; }
        button:hover { background: #111827; }
        .alert { padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.9rem; }
        .alert-ok { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-err { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .err-list { color: #b91c1c; font-size: 0.8rem; margin: -0.5rem 0 0.75rem; }
        .row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
    </style>
</head>
<body>
    <h1>Keycloak User Tools</h1>
    <p class="hint">Versteckte Admin-Funktion. Eingeloggt als: {{ auth('web')->user()?->email }}</p>

    @if (session('status'))
        <div class="alert alert-ok">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-err">{{ session('error') }}</div>
    @endif

    <div class="card">
        <h2>Passwort eines bestehenden Users setzen</h2>
        <form method="POST" action="{{ route('admin.tools.kc.password') }}">
            @csrf
            <input type="hidden" name="t" value="{{ request('t') }}">
            <label>E-Mail</label>
            <input type="email" name="email" value="{{ old('email') }}" required>
            @error('email')<div class="err-list">{{ $message }}</div>@enderror

            <label>Neues Passwort</label>
            <input type="text" name="password" minlength="8" required>
            @error('password')<div class="err-list">{{ $message }}</div>@enderror

            <button type="submit">Passwort setzen</button>
        </form>
    </div>

    <div class="card">
        <h2>Neuen User anlegen</h2>
        <form method="POST" action="{{ route('admin.tools.kc.create') }}">
            @csrf
            <input type="hidden" name="t" value="{{ request('t') }}">

            <div class="row">
                <div>
                    <label>Vorname</label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" required>
                    @error('first_name')<div class="err-list">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label>Nachname</label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" required>
                    @error('last_name')<div class="err-list">{{ $message }}</div>@enderror
                </div>
            </div>

            <label>E-Mail</label>
            <input type="email" name="email" value="{{ old('email') }}" required>
            @error('email')<div class="err-list">{{ $message }}</div>@enderror

            <label>Passwort</label>
            <input type="text" name="password" minlength="8" required>
            @error('password')<div class="err-list">{{ $message }}</div>@enderror

            <button type="submit">User anlegen</button>
        </form>
    </div>
</body>
</html>
