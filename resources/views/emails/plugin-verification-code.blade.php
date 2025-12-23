<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ihr Verifizierungscode</title>
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
        .code-box {
            background: #fff;
            border: 3px solid #3b82f6;
            border-radius: 12px;
            padding: 25px;
            margin: 25px 0;
            text-align: center;
        }
        .code {
            font-family: 'Courier New', monospace;
            font-size: 36px;
            font-weight: bold;
            letter-spacing: 8px;
            color: #1a1a2e;
        }
        .warning-box {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        .warning-box p {
            margin: 0;
            color: #92400e;
            font-size: 14px;
        }
        .info-box {
            background: #dbeafe;
            border: 1px solid #3b82f6;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        .info-box p {
            margin: 0;
            color: #1e40af;
            font-size: 14px;
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
        <p>E-Mail-Verifizierung</p>
    </div>

    <div class="content">
        <p>Hallo {{ $contactName }},</p>

        <p>vielen Dank für die Registrierung beim Global Travel Monitor Plugin. Um die E-Mail-Adresse zu bestätigen, geben Sie bitte den folgenden Code im Abfragefenster ein:</p>

        <div class="code-box">
            <div class="code">{{ $code }}</div>
        </div>

        <div class="info-box">
            <p><strong>Hinweis:</strong> Dieser Code ist {{ $expiryMinutes }} Minuten gültig und kann nur einmal verwendet werden.</p>
        </div>

        <p>Falls Sie diese Registrierung nicht angefordert haben, können Sie diese E-Mail ignorieren.</p>

        <p>Mit freundlichen Grüßen,<br>Ihr Team von Passolution</p>
    </div>

    <div class="footer">
        <p>Diese E-Mail wurde automatisch generiert. Bitte antworten Sie nicht direkt auf diese E-Mail.</p>
        <p>&copy; {{ date('Y') }} Global Travel Monitor</p>
    </div>
</body>
</html>
