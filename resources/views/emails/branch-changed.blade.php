<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $kind }}: {{ $branch->name }}</title>
</head>
<body style="margin: 0; padding: 24px; background: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; color: #18181b; line-height: 1.5;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e4e4e7;">
        <div style="background: #0b3250; padding: 20px 28px; color: #ffffff; font-size: 14px; font-weight: 600;">
            Passolution Travel Information Platform
        </div>

        <div style="padding: 28px;">
            <p style="margin: 0 0 6px 0; font-size: 13px; color: #71717a;">{{ $kind }} – vom Kunden unter Einstellungen › Organisationsstruktur</p>
            <h1 style="margin: 0 0 20px 0; font-size: 20px; line-height: 1.3;">{{ $branch->name }}</h1>

            <table role="presentation" cellpadding="0" cellspacing="0" style="width: 100%; margin: 0 0 20px 0; font-size: 14px;">
                <tr>
                    <td style="padding: 4px 16px 4px 0; color: #71717a; white-space: nowrap;">Kunde</td>
                    <td style="padding: 4px 0;">{{ $ownerName }}@if ($owner) ({{ $owner->app_code ?: '–' }}, #{{ $owner->id }})@endif</td>
                </tr>
                <tr>
                    <td style="padding: 4px 16px 4px 0; color: #71717a; white-space: nowrap;">Abrechnung</td>
                    <td style="padding: 4px 0;">{{ $owner?->currentBillingPeriod?->label() ?? 'kein Zeitraum erfasst' }}</td>
                </tr>
                <tr>
                    <td style="padding: 4px 16px 4px 0; color: #71717a; white-space: nowrap;">PDS-ID</td>
                    <td style="padding: 4px 0;">{{ $owner?->pds_account_id ?: '–' }}</td>
                </tr>
                <tr>
                    <td style="padding: 4px 16px 4px 0; color: #71717a; white-space: nowrap;">App-ID Filiale</td>
                    <td style="padding: 4px 0;">{{ $branch->app_code ?: '–' }}</td>
                </tr>
                <tr>
                    <td style="padding: 4px 16px 4px 0; color: #71717a; white-space: nowrap;">Startdatum</td>
                    <td style="padding: 4px 0; font-weight: 600;">{{ $branch->start_date?->format('d.m.Y') ?? '–' }}</td>
                </tr>
                @if ($branch->end_date)
                    <tr>
                        <td style="padding: 4px 16px 4px 0; color: #71717a; white-space: nowrap;">Enddatum</td>
                        <td style="padding: 4px 0; font-weight: 600;">{{ $branch->end_date->format('d.m.Y') }}</td>
                    </tr>
                @endif
                @if ($scheduledAt)
                    <tr>
                        <td style="padding: 4px 16px 4px 0; color: #71717a; white-space: nowrap;">Löschung am</td>
                        <td style="padding: 4px 0;">{{ $scheduledAt->format('d.m.Y') }}</td>
                    </tr>
                @endif
                @if ($reason)
                    <tr>
                        <td style="padding: 4px 16px 4px 0; color: #71717a; white-space: nowrap; vertical-align: top;">Grund</td>
                        <td style="padding: 4px 0; white-space: pre-line;">{{ $reason }}</td>
                    </tr>
                @endif
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

            @include('emails.partials.change-rows', ['rows' => $rows])

            @if ($adminUrl)
                <a href="{{ $adminUrl }}" style="display: inline-block; margin: 0 8px 8px 0; background: #0b3250; color: #ffffff; text-decoration: none; padding: 11px 22px; border-radius: 8px; font-weight: 600; font-size: 14px;">
                    In Admin v2 öffnen
                </a>
            @endif
            @if ($backendUrl)
                <a href="{{ $backendUrl }}" style="display: inline-block; margin: 0 8px 8px 0; background: #ffffff; color: #0b3250; text-decoration: none; padding: 10px 21px; border-radius: 8px; border: 1px solid #0b3250; font-weight: 600; font-size: 14px;">
                    Im pds-admin öffnen
                </a>
            @endif
        </div>
    </div>
</body>
</html>
