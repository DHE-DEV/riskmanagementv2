<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Config;

class ResetPasswordNotification extends ResetPassword
{
    protected function resetUrl($notifiable)
    {
        if (static::$createUrlCallback) {
            return call_user_func(static::$createUrlCallback, $notifiable, $this->token);
        }

        return url(route('customer.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
    }

    protected function buildMailMessage($url)
    {
        $expireMinutes = Config::get('auth.passwords.customers.expire', 60);

        return (new MailMessage)
            ->from(config('mail.from.address'), 'Global Travel Monitor')
            ->subject('Passwort zurücksetzen')
            ->line('Sie erhalten diese E-Mail, weil wir eine Anfrage zum Zurücksetzen des Passworts für Ihr Konto erhalten haben.')
            ->action('Passwort zurücksetzen', $url)
            ->line("Dieser Link zum Zurücksetzen des Passworts ist {$expireMinutes} Minuten gültig.")
            ->line('Falls Sie kein Zurücksetzen des Passworts angefordert haben, ist keine weitere Aktion erforderlich.');
    }
}
