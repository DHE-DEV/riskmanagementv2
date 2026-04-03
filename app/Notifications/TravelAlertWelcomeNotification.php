<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class TravelAlertWelcomeNotification extends VerifyEmail
{
    public function __construct(
        public array $orderData
    ) {}

    protected function verificationUrl($notifiable)
    {
        if (static::$createUrlCallback) {
            return call_user_func(static::$createUrlCallback, $notifiable);
        }

        return URL::temporarySignedRoute(
            'customer.verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }

    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);
        $loginUrl = route('customer.login');
        $passwordResetUrl = route('customer.password.request');

        return (new MailMessage)
            ->from(config('mail.from.address'), 'Passolution Travel Information Platform')
            ->subject('Ihre Travel Alert-Bestellung - E-Mail-Adresse bestätigen')
            ->view('emails.travel-alert-welcome', [
                'orderData' => $this->orderData,
                'verificationUrl' => $verificationUrl,
                'loginUrl' => $loginUrl,
                'passwordResetUrl' => $passwordResetUrl,
                'customerName' => $notifiable->name,
            ]);
    }
}
