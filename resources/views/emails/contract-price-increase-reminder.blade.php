<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preiserhöhung {{ $contract->increaseLabel() }}</title>
</head>
<body style="margin: 0; padding: 24px; background: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; color: #18181b; line-height: 1.5;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e4e4e7;">
        <div style="background: #0b3250; padding: 20px 28px; color: #ffffff; font-size: 14px; font-weight: 600;">
            Passolution Travel Information Platform
        </div>

        <div style="padding: 28px;">
            <p style="margin: 0 0 6px 0; font-size: 13px; color: #71717a;">Erinnerung: vereinbarte Preiserhöhung steht an</p>
            <h1 style="margin: 0 0 20px 0; font-size: 20px; line-height: 1.3;">{{ $customer->company_name ?: $customer->name }}</h1>

            <table role="presentation" cellpadding="0" cellspacing="0" style="width: 100%; margin: 0 0 20px 0; font-size: 14px;">
                <tr><td style="padding: 4px 16px 4px 0; color: #71717a; white-space: nowrap;">Vertrag</td><td style="padding: 4px 0;">{{ $contract->name }} ({{ $contract->rangeLabel() }})</td></tr>
                <tr><td style="padding: 4px 16px 4px 0; color: #71717a; white-space: nowrap;">Kundennummer</td><td style="padding: 4px 0;">{{ $customer->app_code ?: '–' }} (#{{ $customer->id }})</td></tr>
                <tr><td style="padding: 4px 16px 4px 0; color: #71717a; white-space: nowrap;">Erhöhung</td><td style="padding: 4px 0; font-weight: 600;">{{ $contract->increaseLabel() }}</td></tr>
                <tr><td style="padding: 4px 16px 4px 0; color: #71717a; white-space: nowrap;">Stichtag</td><td style="padding: 4px 0; font-weight: 600;">{{ $anniversary->format('d.m.Y') }}</td></tr>
                @if ($contract->notice_period)
                    <tr><td style="padding: 4px 16px 4px 0; color: #71717a; white-space: nowrap;">Kündigungsfrist</td><td style="padding: 4px 0;">{{ $contract->notice_period }}</td></tr>
                @endif
            </table>

            @if ($rows)
                <table role="presentation" cellpadding="0" cellspacing="0" style="width: 100%; margin: 0 0 20px 0; font-size: 14px; border-collapse: collapse;">
                    <tr>
                        <th style="padding: 8px 10px; text-align: left; font-size: 12px; color: #71717a; border-bottom: 1px solid #e4e4e7;">Individueller Preis</th>
                        <th style="padding: 8px 10px; text-align: right; font-size: 12px; color: #71717a; border-bottom: 1px solid #e4e4e7;">Heute</th>
                        <th style="padding: 8px 10px; text-align: right; font-size: 12px; color: #71717a; border-bottom: 1px solid #e4e4e7;">Nach Erhöhung</th>
                    </tr>
                    @foreach ($rows as $row)
                        <tr>
                            <td style="padding: 6px 10px; border-bottom: 1px solid #f4f4f5;">{{ $row['label'] }}</td>
                            <td style="padding: 6px 10px; border-bottom: 1px solid #f4f4f5; text-align: right; white-space: nowrap;">{{ $row['old'] }}</td>
                            <td style="padding: 6px 10px; border-bottom: 1px solid #f4f4f5; text-align: right; white-space: nowrap; font-weight: 600;">{{ $row['new'] }}</td>
                        </tr>
                    @endforeach
                </table>
            @else
                <p style="margin: 0 0 20px 0; color: #3f3f46;">Im Vertrag sind keine individuellen Beträge hinterlegt – es gelten die Standardpreise.</p>
            @endif

            <p style="margin: 0 0 20px 0; font-size: 13px; color: #71717a;">Es wurde nichts automatisch geändert. Neue Preise bitte im Vertrag hinterlegen.</p>

            <a href="{{ $adminUrl }}" style="display: inline-block; background: #0b3250; color: #ffffff; text-decoration: none; padding: 11px 22px; border-radius: 8px; font-weight: 600; font-size: 14px;">
                Vertrag in Admin v2 öffnen
            </a>
        </div>
    </div>
</body>
</html>
