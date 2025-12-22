<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zugriff verweigert</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 16px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        .icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }
        .icon-logo {
            width: 50px;
            height: 50px;
            object-fit: contain;
            filter: brightness(0) invert(1);
        }
        h1 {
            color: #1F2937;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 12px;
        }
        p {
            color: #6B7280;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .error-message {
            background: #FEF2F2;
            border: 1px solid #FECACA;
            border-radius: 8px;
            padding: 16px;
            color: #991B1B;
            font-size: 14px;
            margin-bottom: 24px;
        }
        .help {
            background: #F3F4F6;
            border-radius: 8px;
            padding: 16px;
            text-align: left;
        }
        .help h3 {
            color: #374151;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .help code {
            display: block;
            background: #E5E7EB;
            padding: 12px;
            border-radius: 6px;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 12px;
            color: #1F2937;
            overflow-x: auto;
            word-break: break-all;
        }
        .register-link {
            display: inline-block;
            margin-top: 24px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            font-size: 14px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .register-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .logo {
            margin-top: 24px;
            color: #9CA3AF;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .logo-img {
            height: 24px;
            width: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <img src="https://global-travel-monitor.eu/images/logo.svg" alt="Global Travel Monitor" class="icon-logo">
        </div>
        <h1>Zugriff verweigert</h1>
        <p>Diese Embed-Ansicht erfordert einen gültigen API-Key.</p>

        <div class="error-message">
            {{ $message }}
        </div>

        <div class="help">
            <h3>So binden Sie das Widget ein:</h3>
            <code>&lt;iframe src="https://global-travel-monitor.eu/embed/dashboard?key=IHR_API_KEY" width="100%" height="800"&gt;&lt;/iframe&gt;</code>
        </div>

        <a href="https://global-travel-monitor.eu/plugin/register" target="_blank" class="register-link">
            Jetzt kostenlos registrieren und API-Key erhalten
        </a>

        <div class="logo">
            <img src="https://global-travel-monitor.eu/images/logo.svg" alt="Logo" class="logo-img">
            Global Travel Monitor
        </div>
    </div>
</body>
</html>
