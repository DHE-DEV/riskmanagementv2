<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $typeLabel }} geändert: {{ $customerName }}</title>
</head>
<body style="margin: 0; padding: 24px; background: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; color: #18181b; line-height: 1.5;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e4e4e7;">
        <div style="background: #0b3250; padding: 20px 28px; color: #ffffff; font-size: 14px; font-weight: 600;">
            Passolution Travel Information Platform
        </div>

        <div style="padding: 28px;">
            <p style="margin: 0 0 6px 0; font-size: 13px; color: #71717a;">Ein Kunde hat seine {{ $typeLabel }} in den Einstellungen geändert.</p>
            <h1 style="margin: 0 0 20px 0; font-size: 20px; line-height: 1.3;">{{ $customerName }}</h1>

            <table role="presentation" cellpadding="0" cellspacing="0" style="width: 100%; margin: 0 0 20px 0; font-size: 14px;">
                <tr>
                    <td style="padding: 4px 16px 4px 0; color: #71717a; white-space: nowrap;">Kundennummer</td>
                    <td style="padding: 4px 0;">{{ $customer->app_code ?: '–' }} (#{{ $customer->id }})</td>
                </tr>
                <tr>
                    <td style="padding: 4px 16px 4px 0; color: #71717a; white-space: nowrap;">Abrechnung</td>
                    <td style="padding: 4px 0;">{{ $customer?->currentBillingPeriod?->label() ?? 'kein Zeitraum erfasst' }}</td>
                </tr>
                <tr>
                    <td style="padding: 4px 16px 4px 0; color: #71717a; white-space: nowrap;">PDS-ID</td>
                    <td style="padding: 4px 0;">{{ $customer->pds_account_id ?: '–' }}</td>
                </tr>
                <tr>
                    <td style="padding: 4px 16px 4px 0; color: #71717a; white-space: nowrap;">E-Mail</td>
                    <td style="padding: 4px 0;">{{ $customer->email ?: '–' }}</td>
                </tr>
                @if ($changedBy)
                    <tr>
                        <td style="padding: 4px 16px 4px 0; color: #71717a; white-space: nowrap;">Geändert von</td>
                        <td style="padding: 4px 0;">{{ $changedBy }}</td>
                    </tr>
                @endif
                <tr>
                    <td style="padding: 4px 16px 4px 0; color: #71717a; white-space: nowrap;">Zeitpunkt</td>
                    <td style="padding: 4px 0;">{{ now()->timezone('Europe/Berlin')->format('d.m.Y H:i') }} Uhr</td>
                </tr>
            </table>

            <table role="presentation" cellpadding="0" cellspacing="0" style="width: 100%; margin: 0 0 24px 0; font-size: 14px; border-collapse: collapse;">
                <tr>
                    <th style="padding: 8px 10px; text-align: left; font-size: 12px; color: #71717a; border-bottom: 1px solid #e4e4e7;">{{ $typeLabel }}</th>
                    <th style="padding: 8px 10px; text-align: left; font-size: 12px; color: #71717a; border-bottom: 1px solid #e4e4e7;">Bisher</th>
                    <th style="padding: 8px 10px; text-align: left; font-size: 12px; color: #71717a; border-bottom: 1px solid #e4e4e7;">Neu</th>
                </tr>
                @foreach ($rows as $row)
                    <tr style="{{ $row['changed'] ? 'background: #fef9c3;' : '' }}">
                        <td style="padding: 6px 10px; color: #71717a; white-space: nowrap; border-bottom: 1px solid #f4f4f5;">{{ $row['label'] }}</td>
                        <td style="padding: 6px 10px; border-bottom: 1px solid #f4f4f5; {{ $row['changed'] ? 'color: #71717a; text-decoration: line-through;' : '' }}">{{ $row['old'] !== '' ? $row['old'] : '–' }}</td>
                        <td style="padding: 6px 10px; border-bottom: 1px solid #f4f4f5; {{ $row['changed'] ? 'font-weight: 600;' : '' }}">{{ $row['new'] !== '' ? $row['new'] : '–' }}</td>
                    </tr>
                @endforeach
            </table>

            <a href="{{ $adminUrl }}" style="display: inline-block; margin: 0 8px 8px 0; background: #0b3250; color: #ffffff; text-decoration: none; padding: 11px 22px; border-radius: 8px; font-weight: 600; font-size: 14px;">
                In Admin v2 öffnen
            </a>
            @if ($backendUrl)
                <a href="{{ $backendUrl }}" style="display: inline-block; margin: 0 8px 8px 0; background: #ffffff; color: #0b3250; text-decoration: none; padding: 10px 21px; border-radius: 8px; border: 1px solid #0b3250; font-weight: 600; font-size: 14px;">
                    Im pds-admin öffnen
                </a>
            @else
                <p style="margin: 8px 0 0 0; font-size: 13px; color: #71717a;">Keine PDS-ID hinterlegt – kein Link ins pds-admin möglich.</p>
            @endif
        </div>
    </div>
</body>
</html>
