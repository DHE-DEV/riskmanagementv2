<html>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #002742; color: white; padding: 20px 30px; border-radius: 8px 8px 0 0;">
        <h1 style="margin: 0; font-size: 22px;">
            @if($stage === 'confirmed')
                {{ $awaitsApproval ? 'Bestellung wartet auf Freischaltung' : 'Travel Alert-Bestellung bestätigt' }}
            @else
                Neue Travel Alert-Bestellung
            @endif
        </h1>
    </div>

    <div style="background: #f8f9fa; padding: 25px 30px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px;">

        @if($stage === 'confirmed' && $awaitsApproval)
            <div style="padding: 12px 16px; margin-bottom: 20px; border-radius: 6px; background: #fef3c7; border: 1px solid #fcd34d; color: #92400e;">
                <strong>⏳ Freischaltung erforderlich:</strong>
                Der Kunde hat die Bestellung bestätigt. Der Zugang ist noch nicht aktiv –
                bitte die Bestellung im Admin-Bereich freischalten.
            </div>
        @elseif($stage === 'confirmed')
            <div style="padding: 12px 16px; margin-bottom: 20px; border-radius: 6px; background: #d1fae5; border: 1px solid #6ee7b7; color: #065f46;">
                <strong>✅ Bestätigt:</strong>
                Der Kunde hat die Bestellung bestätigt, der Zugang wurde automatisch freigeschaltet.
            </div>
        @else
            <div style="padding: 12px 16px; margin-bottom: 20px; border-radius: 6px; background: #e5e7eb; border: 1px solid #d1d5db; color: #374151;">
                <strong>✉️ Wartet auf Bestätigung:</strong>
                Der Kunde hat eine Bestätigungsmail erhalten. Der Zugang wird erst nach dem
                Klick auf den Bestätigungslink eingerichtet.
            </div>
        @endif

        <div style="padding: 12px 16px; margin-bottom: 20px; border-radius: 6px; {{ $isNewCustomer ? 'background: #d1fae5; border: 1px solid #6ee7b7; color: #065f46;' : 'background: #dbeafe; border: 1px solid #93c5fd; color: #1e40af;' }}">
            <strong>{{ $isNewCustomer ? '🆕 Neukunde' : '👤 Bestandskunde' }}:</strong>
            {{ $isNewCustomer ? 'Es wurde ein neues Kundenkonto in der Passolution Travel Information Platform angelegt.' : 'Die Bestellung wurde für einen bestehenden Kunden durchgeführt.' }}
            @if($customerUrl)
                <br><a href="{{ $customerUrl }}" style="color: inherit; font-weight: bold;">→ Kunde im Admin-Bereich öffnen</a>
            @endif
        </div>

        <h2 style="color: #002742; font-size: 16px; margin-top: 0; border-bottom: 2px solid #CEE741; padding-bottom: 8px;">Firmendaten</h2>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 6px 0; font-weight: bold; width: 200px; vertical-align: top;">Firmenname:</td>
                <td style="padding: 6px 0;">{{ $orderData['company'] }}</td>
            </tr>
            <tr>
                <td style="padding: 6px 0; font-weight: bold; vertical-align: top;">Ansprechpartner:</td>
                <td style="padding: 6px 0;">{{ trim(($orderData['first_name'] ?? '') . ' ' . ($orderData['last_name'] ?? '')) ?: '–' }}</td>
            </tr>
            <tr>
                <td style="padding: 6px 0; font-weight: bold; vertical-align: top;">E-Mail:</td>
                <td style="padding: 6px 0;"><a href="mailto:{{ $orderData['email'] }}">{{ $orderData['email'] }}</a></td>
            </tr>
            <tr>
                <td style="padding: 6px 0; font-weight: bold; vertical-align: top;">Telefon:</td>
                <td style="padding: 6px 0;">{{ $orderData['phone'] }}</td>
            </tr>
        </table>

        <h2 style="color: #002742; font-size: 16px; margin-top: 20px; border-bottom: 2px solid #CEE741; padding-bottom: 8px;">Adresse</h2>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 6px 0; font-weight: bold; width: 200px; vertical-align: top;">Straße:</td>
                <td style="padding: 6px 0;">{{ $orderData['street'] }}</td>
            </tr>
            <tr>
                <td style="padding: 6px 0; font-weight: bold; vertical-align: top;">PLZ / Stadt:</td>
                <td style="padding: 6px 0;">{{ $orderData['postal_code'] }} {{ $orderData['city'] }}</td>
            </tr>
            <tr>
                <td style="padding: 6px 0; font-weight: bold; vertical-align: top;">Land:</td>
                <td style="padding: 6px 0;">{{ $orderData['country'] }}</td>
            </tr>
        </table>

        <h2 style="color: #002742; font-size: 16px; margin-top: 20px; border-bottom: 2px solid #CEE741; padding-bottom: 8px;">Abrechnung</h2>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 6px 0; font-weight: bold; width: 200px; vertical-align: top;">Bestehendes Abrechnungs&shy;verfahren:</td>
                <td style="padding: 6px 0;">{{ $orderData['existing_billing'] === 'ja' ? 'Ja' : 'Nein' }}</td>
            </tr>
        </table>

        @if(!empty($orderData['remarks']))
        <h2 style="color: #002742; font-size: 16px; margin-top: 20px; border-bottom: 2px solid #CEE741; padding-bottom: 8px;">Bemerkung</h2>
        <p style="margin: 8px 0; white-space: pre-line;">{{ $orderData['remarks'] }}</p>
        @endif

        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 25px 0 15px;">
        <p style="font-size: 12px; color: #6b7280; margin: 0;">
            Diese Bestellung wurde über das Travel Alert-Bestellformular auf der Passolution Travel Information Platform eingereicht.<br>
            Eingegangen am {{ now()->format('d.m.Y') }} um {{ now()->format('H:i') }} Uhr.
        </p>
    </div>
</body>
</html>
