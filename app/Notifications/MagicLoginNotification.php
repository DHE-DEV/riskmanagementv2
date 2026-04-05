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
        protected string $code,
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
            ->subject('Ihr Login-Code: ' . $this->code)
            ->greeting('Hallo ' . ($notifiable->name ?? '') . '!')
            ->line('Sie haben einen Login-Code für Ihr Konto angefordert.')
            ->line('Ihr Login-Code lautet:')
            ->line('**' . implode(' ', str_split($this->code, 3)) . '**')
            ->line('Geben Sie diesen Code auf der Login-Seite ein.')
            ->line("Dieser Code ist gültig bis zum {$expiresAt}.")
            ->line('Falls Sie diesen Code nicht angefordert haben, können Sie diese E-Mail ignorieren.')
            ->line('Falls Sie Probleme haben, können Sie auch [diesen Link](' . $this->loginUrl . ') verwenden, um sich anzumelden.');
    }
}
