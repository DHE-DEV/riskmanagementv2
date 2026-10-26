<?php

namespace App\Notifications;

use App\Models\Customer;
use App\Services\PdsAccountImportService;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Mail an den Admin, der den Import der Unteraccounts gestartet hat.
 */
class PdsChildrenImportCompleted extends Notification
{
    /**
     * @param  array<string, int>  $result  Status => Anzahl
     */
    public function __construct(
        public Customer $agency,
        public array $result,
        public int $total,
        public ?string $error = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $this->agency->company_name ?: $this->agency->name;
        $url = route('adminv2.customer-management.customers.edit', ['customer' => $this->agency->id, 'tab' => 'sub-accounts']);

        $mail = (new MailMessage)
            ->subject(($this->error ? 'Import abgebrochen: ' : 'Import abgeschlossen: ').'Unteraccounts von '.$name)
            ->greeting($this->error ? 'Der Import ist abgebrochen.' : 'Der Import ist abgeschlossen.')
            ->line("Unteraccounts aus pds-admin für {$name} (Kunde #{$this->agency->id}): {$this->total} geprüft.");

        foreach ($this->result as $status => $count) {
            $mail->line((PdsAccountImportService::STATUS_LABELS[$status] ?? $status).': '.$count);
        }

        if ($this->error) {
            $mail->line($this->error);
        }

        return $mail->action('Unteraccounts ansehen', $url);
    }
}
