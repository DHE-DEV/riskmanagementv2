@extends('emails.layouts.travel-alert')

@section('title', 'Travel Alert freigeschaltet')
@section('subtitle', 'Ihr Zugang steht bereit')

@section('content')
    <p style="font-size: 16px; margin: 0 0 20px 0;">
        Guten Tag{{ $customerName ? ' ' . $customerName : '' }},
    </p>

    <p style="margin: 0 0 24px 0;">
        Ihre Bestellung ist bestätigt und Ihr Travel Alert-Zugang ist freigeschaltet.
    </p>

    <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 20px; margin: 0 0 28px 0;">
        <p style="margin: 0 0 12px 0; font-weight: 700; color: #166534; font-size: 15px;">
            Travel Alert jetzt nutzen
        </p>
        <div style="text-align: center; margin-bottom: 16px;">
            <a href="{{ $travelAlertUrl }}" style="display: inline-block; background: #166534; color: #ffffff; text-decoration: none; padding: 12px 32px; border-radius: 6px; font-weight: 700; font-size: 15px;">
                Zu Travel Alert
            </a>
        </div>
        <div style="background: #ffffff; border-radius: 6px; padding: 14px; font-size: 13px; color: #374151;">
            <p style="margin: 0 0 8px 0; font-weight: 600;">So melden Sie sich an:</p>
            <p style="margin: 0 0 6px 0;">
                Über <strong><a href="{{ $passwordResetUrl }}" style="color: #002742;">Passwort vergessen</a></strong>
                auf der <a href="{{ $loginUrl }}" style="color: #002742;">Login-Seite</a> vergeben Sie sich ein eigenes Passwort.
            </p>
            <p style="margin: 0;">
                Alternativ melden Sie sich <strong>ohne Passwort per E-Mail-Link</strong> an.
                Beide Anmeldearten sind dauerhaft nutzbar.
            </p>
        </div>
    </div>

    @include('emails.partials.travel-alert-order-details')
@endsection
