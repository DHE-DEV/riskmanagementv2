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
            background: #FEE2E2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }
        .icon svg {
            width: 40px;
            height: 40px;
            color: #DC2626;
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
        .logo {
            margin-top: 24px;
            color: #9CA3AF;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
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

        <div class="logo">
            Global Travel Monitor
        </div>
    </div>
</body>
</html>
