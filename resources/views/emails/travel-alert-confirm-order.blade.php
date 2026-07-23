@extends('emails.layouts.travel-alert')

@section('title', 'Travel Alert-Bestellung bestätigen')
@section('subtitle', 'Bitte bestätigen Sie Ihre Bestellung')

@section('content')
    <p style="font-size: 16px; margin: 0 0 20px 0;">
        Guten Tag{{ $customerName ? ' ' . $customerName : '' }},
    </p>

    <p style="margin: 0 0 24px 0;">
        vielen Dank für Ihre Travel Alert-Bestellung. Damit wir sie bearbeiten können,
        bestätigen Sie sie bitte über den folgenden Button.
    </p>

    <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 20px; margin: 0 0 28px 0;">
        <p style="margin: 0 0 12px 0; font-weight: 700; color: #1e40af; font-size: 15px;">
            Bestellung bestätigen
        </p>
        <p style="margin: 0 0 16px 0; font-size: 14px; color: #1e40af;">
            Ohne diese Bestätigung wird der Zugang nicht eingerichtet.
        </p>
        <div style="text-align: left;">
            <a href="{{ $confirmationUrl }}" style="display: inline-block; background: #002742; color: #ffffff; text-decoration: none; padding: 14px 36px; border-radius: 8px; font-weight: 700; font-size: 15px;">
                Bestellung jetzt bestätigen
            </a>
        </div>
        <p style="margin: 16px 0 0 0; font-size: 13px; color: #1e40af; text-align: left;">
            Der Link ist bis zum {{ $expiresAt->format('d.m.Y, H:i') }} Uhr gültig.
        </p>
    </div>

    @include('emails.partials.travel-alert-order-details')

    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 28px 0;">

    <p style="margin: 0; font-size: 13px; color: #6b7280;">
        Sie haben keine Bestellung ausgelöst? Dann ignorieren Sie diese E-Mail einfach –
        ohne Bestätigung passiert nichts.
    </p>
@endsection
