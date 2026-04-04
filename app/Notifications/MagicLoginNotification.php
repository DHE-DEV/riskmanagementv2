<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;

class MagicLoginNotification extends Notification
{
    public function __construct(
        protected string $loginUrl,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $expireMinutes = Config::get('auth.magic_login.expire', 15);
        $expiresAt = Carbon::now()
            ->addMinutes($expireMinutes)
            ->timezone('Europe/Berlin')
            ->format('d.m.Y \u\m H:i \U\h\r');

        return (new MailMessage)
            ->from(config('mail.from.address'), 'Passolution Travel Information Platform')
            ->subject('Ihr Login-Link')
            ->greeting('Hallo ' . ($notifiable->name ?? '') . '!')
            ->line('Sie haben einen Login-Link für Ihr Konto angefordert.')
            ->line('Klicken Sie auf die Schaltfläche unten, um sich anzumelden:')
            ->action('Jetzt anmelden', $this->loginUrl)
            ->line("Dieser Link ist gültig bis zum {$expiresAt}.")
            ->line('Falls Sie diesen Link nicht angefordert haben, können Sie diese E-Mail ignorieren.');
    }
}
