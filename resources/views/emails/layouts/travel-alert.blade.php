<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Travel Alert')</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.7; color: #333; max-width: 640px; margin: 0 auto; padding: 0; background: #f3f4f6;">
    <!-- Header -->
    <div style="background: #002742; color: white; padding: 28px 32px; border-radius: 8px 8px 0 0; text-align: center;">
        <h1 style="margin: 0 0 4px 0; font-size: 24px; font-weight: 700;">Passolution Travel Information Platform</h1>
        <p style="margin: 0; font-size: 14px; color: rgba(255,255,255,0.7);">@yield('subtitle', 'Ihre Travel Alert-Bestellung')</p>
    </div>

    <div style="background: #ffffff; padding: 32px; border: 1px solid #e5e7eb; border-top: none;">
        @yield('content')
    </div>

    <!-- Footer -->
    <div style="background: #f9fafb; padding: 20px 32px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px; text-align: center;">
        <p style="margin: 0 0 4px 0; font-size: 12px; color: #9ca3af;">
            Diese E-Mail wurde automatisch generiert. Bitte antworten Sie nicht direkt auf diese E-Mail.
        </p>
        <p style="margin: 0; font-size: 12px; color: #9ca3af;">
            &copy; {{ date('Y') }} Passolution Travel Information Platform
        </p>
    </div>
</body>
</html>
