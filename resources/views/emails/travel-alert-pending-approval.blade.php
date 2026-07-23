@extends('emails.layouts.travel-alert')

@section('title', 'Travel Alert-Bestellung bestätigt')
@section('subtitle', 'Ihre Bestellung wird geprüft')

@section('content')
    <p style="font-size: 16px; margin: 0 0 20px 0;">
        Guten Tag{{ $customerName ? ' ' . $customerName : '' }},
    </p>

    <p style="margin: 0 0 24px 0;">
        vielen Dank für die Bestätigung Ihrer Travel Alert-Bestellung für <strong>{{ $company }}</strong>.
    </p>

    <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 20px; margin: 0 0 24px 0;">
        <p style="margin: 0 0 10px 0; font-weight: 700; color: #92400e; font-size: 15px;">
            Ihre Bestellung liegt uns vor
        </p>
        <p style="margin: 0; font-size: 14px; color: #92400e;">
            Ein Mitarbeiter prüft die Bestellung und schaltet Ihren Zugang frei.
            Sobald das erledigt ist, erhalten Sie eine weitere E-Mail von uns.
        </p>
    </div>

    <p style="margin: 0; font-size: 14px; color: #4b5563;">
        Bei Rückfragen erreichen Sie uns unter
        <a href="mailto:info@passolution.de" style="color: #002742;">info@passolution.de</a>.
    </p>
@endsection
