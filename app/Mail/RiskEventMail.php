<?php

namespace App\Mail;

use App\Models\NotificationRule;
use App\Models\NotificationTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class RiskEventMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $renderedHtml;

    public function __construct(
        public NotificationTemplate $template,
        public array $placeholders,
        public NotificationRule $rule,
    ) {
        $html = $template->body_html;

        if (array_key_exists('{unsubscribe_url}', $placeholders)) {
            $html .= '<div style="margin-top: 30px; padding-top: 15px; border-top: 1px solid #eee; text-align: center;">'
                . '<p style="color: #999; font-size: 11px;">'
                . '<a href="{unsubscribe_url}" style="color: #999;">Von diesen Benachrichtigungen abmelden</a>'
                . '</p>'
                . '</div>';
        }

        $this->renderedHtml = $this->replacePlaceholders($html, $placeholders);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->replacePlaceholders($this->template->subject, $this->placeholders),
        );
    }

    public function headers(): Headers
    {
        $headers = new Headers;

        if (array_key_exists('{unsubscribe_url}', $this->placeholders)) {
            $unsubscribeUrl = $this->placeholders['{unsubscribe_url}'] ?? '';

            if ($unsubscribeUrl) {
                $headers->text([
                    'List-Unsubscribe' => "<{$unsubscribeUrl}>",
                ]);
            }
        }

        return $headers;
    }

    public function build(): self
    {
        $mail = $this->html($this->renderedHtml);

        $toRecipients = $this->rule->recipients->where('recipient_type', 'to');
        $ccRecipients = $this->rule->recipients->where('recipient_type', 'cc');
        $bccRecipients = $this->rule->recipients->where('recipient_type', 'bcc');

        // First TO recipient is already set via Mail::to(), add remaining
        foreach ($toRecipients->skip(1) as $recipient) {
            $mail->to($recipient->email);
        }

        foreach ($ccRecipients as $recipient) {
            $mail->cc($recipient->email);
        }

        foreach ($bccRecipients as $recipient) {
            $mail->bcc($recipient->email);
        }

        return $mail;
    }

    private function replacePlaceholders(string $text, array $placeholders): string
    {
        foreach ($placeholders as $key => $value) {
            $text = str_replace($key, $value ?? '', $text);
        }

        return $text;
    }
}
