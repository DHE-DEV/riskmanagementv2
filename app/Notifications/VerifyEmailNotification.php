<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;

class VerifyEmailNotification extends VerifyEmail
{
    protected function buildMailMessage($url)
    {
        $expireMinutes = Config::get('auth.verification.expire', 60);
        $expiresAt = Carbon::now()
            ->addMinutes($expireMinutes)
            ->timezone('Europe/Berlin')
            ->format('d.m.Y \u\m H:i \U\h\r');

        return (new MailMessage)
            ->subject('E-Mail-Adresse bestätigen')
            ->line('Bitte klicken Sie auf die Schaltfläche unten, um Ihre E-Mail-Adresse zu bestätigen.')
            ->action('E-Mail-Adresse bestätigen', $url)
            ->line("Dieser Link ist gültig bis zum {$expiresAt}.")
            ->line('Falls Sie kein Konto erstellt haben, ist keine weitere Aktion erforderlich.');
    }
}
