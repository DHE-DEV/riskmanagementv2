<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>Abmelden …</title>
    <style>
        html, body { height: 100%; margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; background: #f9fafb; color: #6b7280; }
        .wrap { height: 100%; display: flex; align-items: center; justify-content: center; }
    </style>
</head>
<body>
    <div class="wrap">Abmeldung läuft …</div>

    {{-- Homepage-Session im SELBEN eingebetteten Kontext wie der iframe beenden,
         damit das Cross-Site-Cookie mitgesendet wird. --}}
    <iframe src="{{ $homepageLogout }}" style="position:absolute;width:0;height:0;border:0;left:-9999px"
            referrerpolicy="no-referrer" onload="window.__pdsLogoutDone && window.__pdsLogoutDone()"></iframe>

    <script>
        (function () {
            var finalUrl = @json($finalUrl);
            var redirected = false;
            function go() {
                if (redirected) return;
                redirected = true;
                window.location.replace(finalUrl);
            }
            window.__pdsLogoutDone = go;
            // Fallback, falls das iframe-onload nicht (rechtzeitig) feuert.
            setTimeout(go, 2500);
        })();
    </script>
</body>
</html>
