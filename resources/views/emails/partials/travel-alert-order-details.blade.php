{{-- Bestelldaten-Uebersicht, geteilt von Bestaetigungs- und Freischaltungsmail. --}}
<h2 style="color: #002742; font-size: 16px; margin: 0 0 4px 0; padding-bottom: 8px; border-bottom: 3px solid #CEE741;">
    Ihre Bestelldaten
</h2>

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

<h3 style="color: #374151; font-size: 14px; margin: 20px 0 8px 0; text-transform: uppercase; letter-spacing: 0.5px;">Abrechnung</h3>
<table style="width: 100%; border-collapse: collapse; font-size: 14px;">
    <tr>
        <td style="padding: 5px 0; font-weight: 600; width: 160px; vertical-align: top; color: #6b7280;">Bestehendes Verfahren</td>
        <td style="padding: 5px 0;">{{ ($orderData['existing_billing'] ?? '') === 'ja' ? 'Ja' : 'Nein' }}</td>
    </tr>
</table>

@if(!empty($orderData['remarks']))
<h3 style="color: #374151; font-size: 14px; margin: 20px 0 8px 0; text-transform: uppercase; letter-spacing: 0.5px;">Bemerkungen</h3>
<p style="margin: 0; font-size: 14px; white-space: pre-line; background: #f9fafb; padding: 12px; border-radius: 6px; border: 1px solid #e5e7eb;">{{ $orderData['remarks'] }}</p>
@endif
