<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ihr Global Travel Monitor Plugin-Zugang</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #1a1a2e;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .key-box {
            background: #fff;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
            word-break: break-all;
        }
        .code-block {
            background: #1a1a2e;
            color: #4ade80;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            white-space: pre-wrap;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
        }
        .domain-list {
            background: #fff;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }
        .domain-item {
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .domain-item:last-child {
            border-bottom: none;
        }
        h2 {
            color: #1a1a2e;
            margin-top: 30px;
        }
        .footer {
            text-align: center;
            color: #6c757d;
            font-size: 12px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Global Travel Monitor</h1>
        <p>Ihr Plugin-Zugang</p>
    </div>

    <div class="content">
        <p>Hallo {{ $contactName }},</p>

        <p>Ihr Plugin-Zugang für <strong>{{ $companyName }}</strong> wurde erfolgreich erstellt!</p>

        <h2>Ihr API-Key</h2>
        <div class="key-box">
            {{ $publicKey }}
        </div>
        <p><small>Bewahren Sie diesen Key sicher auf. Er wird für die Authentifizierung Ihres Widgets benötigt.</small></p>

        <h2>Registrierte Domains</h2>
        <div class="domain-list">
            @foreach($domains as $domain)
                <div class="domain-item">{{ $domain }}</div>
            @endforeach
        </div>

        <h2>Einbindecode</h2>
        <p>Kopieren Sie den folgenden Code und fügen Sie ihn in Ihre Website ein:</p>

        <div class="code-block">{{ $embedSnippet }}</div>

        <p>
            <a href="{{ $dashboardUrl }}" class="button">Zum Plugin Dashboard</a>
        </p>

        <p style="margin-top: 30px;">
            Bei Fragen stehen wir Ihnen gerne zur Verfügung.
        </p>

        <p>Mit freundlichen Grüßen,<br>Ihr Global Travel Monitor Team</p>
    </div>

    <div class="footer">
        <p>Diese E-Mail wurde automatisch generiert. Bitte antworten Sie nicht direkt auf diese E-Mail.</p>
        <p>&copy; {{ date('Y') }} Global Travel Monitor</p>
    </div>
</body>
</html>
