<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Anmeldung erfolgreich</title>
    <style>
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            display: flex; align-items: center; justify-content: center;
            height: 100vh; margin: 0; color: #374151; background: #fff;
        }
    </style>
</head>
<body>
    <p>Anmeldung erfolgreich – einen Moment&nbsp;…</p>
    <script>
        (function () {
            var dashboard = @json(url('/customer/dashboard'));
            try {
                if (window.parent && window.parent !== window) {
                    // Im iframe (Login-Modal): Parent ist same-origin (Plattform) ->
                    // targetOrigin = eigene Origin. Parent laedt nach Erhalt neu.
                    window.parent.postMessage({ type: 'pds-platform-login-success' }, window.location.origin);
                } else {
                    // Direktaufruf (nicht im iframe): normal weiter zum Dashboard.
                    window.location.replace(dashboard);
                }
            } catch (e) {
                window.location.replace(dashboard);
            }
        })();
    </script>
</body>
</html>
