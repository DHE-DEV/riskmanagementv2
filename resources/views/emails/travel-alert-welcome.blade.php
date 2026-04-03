<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Alert-Bestellung</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.7; color: #333; max-width: 640px; margin: 0 auto; padding: 0; background: #f3f4f6;">
    <!-- Header -->
    <div style="background: #002742; color: white; padding: 28px 32px; border-radius: 8px 8px 0 0; text-align: center;">
        <h1 style="margin: 0 0 4px 0; font-size: 24px; font-weight: 700;">Passolution Travel Information Platform</h1>
        <p style="margin: 0; font-size: 14px; color: rgba(255,255,255,0.7);">Ihre Travel Alert-Bestellung</p>
    </div>

    <div style="background: #ffffff; padding: 32px; border: 1px solid #e5e7eb; border-top: none;">
        <!-- Greeting -->
        <p style="font-size: 16px; margin: 0 0 20px 0;">
            Guten Tag{{ $customerName ? ' ' . $customerName : '' }},
        </p>

        <p style="margin: 0 0 24px 0;">
            vielen Dank, dass Sie sich für Travel Alert entschieden haben. Ihre Bestellung ist bei uns eingegangen und Ihr Zugang wurde automatisch freigeschaltet.
        </p>

        <!-- Email Verification -->
        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 20px; margin: 0 0 28px 0;">
            <p style="margin: 0 0 12px 0; font-weight: 700; color: #1e40af; font-size: 15px;">
                Bitte bestätigen Sie Ihre E-Mail-Adresse
            </p>
            <p style="margin: 0 0 16px 0; font-size: 14px; color: #1e40af;">
                Um Ihren Account zu aktivieren und Travel Alert nutzen zu können, bestätigen Sie bitte Ihre E-Mail-Adresse über den folgenden Button:
            </p>
            <div style="text-align: center;">
                <a href="{{ $verificationUrl }}" style="display: inline-block; background: #002742; color: #ffffff; text-decoration: none; padding: 14px 36px; border-radius: 8px; font-weight: 700; font-size: 15px;">
                    E-Mail-Adresse bestätigen
                </a>
            </div>
        </div>

        <!-- Order Details -->
        <h2 style="color: #002742; font-size: 16px; margin: 0 0 4px 0; padding-bottom: 8px; border-bottom: 3px solid #CEE741;">
            Ihre Bestelldaten
        </h2>

        <!-- Company Data -->
        <h3 style="color: #374151; font-size: 14px; margin: 20px 0 8px 0; text-transform: uppercase; letter-spacing: 0.5px;">Firmendaten</h3>
        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
            <tr>
                <td style="padding: 5px 0; font-weight: 600; width: 160px; vertical-align: top; color: #6b7280;">Firmenname</td>
                <td style="padding: 5px 0;">{{ $orderData['company'] }}</td>
            </tr>
            @if(trim(($orderData['first_name'] ?? '') . ' ' . ($orderData['last_name'] ?? '')))
            <tr>
                <td style="padding: 5px 0; font-weight: 600; vertical-align: top; color: #6b7280;">Ansprechpartner</td>
                <td style="padding: 5px 0;">{{ trim(($orderData['first_name'] ?? '') . ' ' . ($orderData['last_name'] ?? '')) }}</td>
            </tr>
            @endif
            <tr>
                <td style="padding: 5px 0; font-weight: 600; vertical-align: top; color: #6b7280;">E-Mail</td>
                <td style="padding: 5px 0;">{{ $orderData['email'] }}</td>
            </tr>
            <tr>
                <td style="padding: 5px 0; font-weight: 600; vertical-align: top; color: #6b7280;">Telefon</td>
                <td style="padding: 5px 0;">{{ $orderData['phone'] }}</td>
            </tr>
        </table>

        <!-- Address -->
        <h3 style="color: #374151; font-size: 14px; margin: 20px 0 8px 0; text-transform: uppercase; letter-spacing: 0.5px;">Adresse</h3>
        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
            <tr>
                <td style="padding: 5px 0; font-weight: 600; width: 160px; vertical-align: top; color: #6b7280;">Straße</td>
                <td style="padding: 5px 0;">{{ $orderData['street'] }}</td>
            </tr>
            <tr>
                <td style="padding: 5px 0; font-weight: 600; vertical-align: top; color: #6b7280;">PLZ / Stadt</td>
                <td style="padding: 5px 0;">{{ $orderData['postal_code'] }} {{ $orderData['city'] }}</td>
            </tr>
            <tr>
                <td style="padding: 5px 0; font-weight: 600; vertical-align: top; color: #6b7280;">Land</td>
                <td style="padding: 5px 0;">{{ $orderData['country'] }}</td>
            </tr>
        </table>

        <!-- Billing -->
        <h3 style="color: #374151; font-size: 14px; margin: 20px 0 8px 0; text-transform: uppercase; letter-spacing: 0.5px;">Abrechnung</h3>
        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
            <tr>
                <td style="padding: 5px 0; font-weight: 600; width: 160px; vertical-align: top; color: #6b7280;">Bestehendes Verfahren</td>
                <td style="padding: 5px 0;">{{ $orderData['existing_billing'] === 'ja' ? 'Ja' : 'Nein' }}</td>
            </tr>
        </table>

        @if(!empty($orderData['remarks']))
        <!-- Remarks -->
        <h3 style="color: #374151; font-size: 14px; margin: 20px 0 8px 0; text-transform: uppercase; letter-spacing: 0.5px;">Bemerkungen</h3>
        <p style="margin: 0; font-size: 14px; white-space: pre-line; background: #f9fafb; padding: 12px; border-radius: 6px; border: 1px solid #e5e7eb;">{{ $orderData['remarks'] }}</p>
        @endif

        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 28px 0;">

        <!-- Account Info -->
        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 20px; margin: 0 0 24px 0;">
            <p style="margin: 0 0 10px 0; font-weight: 700; color: #166534; font-size: 15px;">
                Ihr Account wurde erstellt
            </p>
            <p style="margin: 0 0 12px 0; font-size: 14px; color: #166534;">
                Mit der Bestätigung Ihrer E-Mail-Adresse wird Ihr Account aktiviert. Danach können Sie sich jederzeit im Portal anmelden:
            </p>
            <div style="text-align: center; margin-bottom: 16px;">
                <a href="{{ $loginUrl }}" style="display: inline-block; background: #166534; color: #ffffff; text-decoration: none; padding: 10px 28px; border-radius: 6px; font-weight: 600; font-size: 14px;">
                    Zum Login
                </a>
            </div>
            <div style="background: #ffffff; border-radius: 6px; padding: 14px; font-size: 13px; color: #374151;">
                <p style="margin: 0 0 8px 0; font-weight: 600;">So melden Sie sich an:</p>
                <p style="margin: 0 0 6px 0;">
                    Nach der E-Mail-Bestätigung können Sie sich über die Funktion <strong>"Passwort vergessen"</strong> auf der Login-Seite ein eigenes Passwort vergeben.
                </p>
                <p style="margin: 0;">
                    Alternativ können Sie sich jederzeit <strong>ohne Passwort per E-Mail-Link</strong> anmelden. Beide Anmeldearten sind dauerhaft nutzbar.
                </p>
            </div>
        </div>

        <!-- Next Steps -->
        <div style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px;">
            <p style="margin: 0 0 10px 0; font-weight: 700; color: #374151; font-size: 15px;">
                So geht es weiter
            </p>
            <ol style="margin: 0; padding-left: 20px; font-size: 14px; color: #4b5563;">
                <li style="margin-bottom: 6px;">Bestätigen Sie Ihre E-Mail-Adresse über den Button oben</li>
                <li style="margin-bottom: 6px;">Melden Sie sich im Portal an</li>
                <li style="margin-bottom: 0;">Nutzen Sie Travel Alert direkt - Ihr Zugang ist bereits freigeschaltet</li>
            </ol>
        </div>
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
