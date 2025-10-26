<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BranchExportCompleted extends Notification
{
    use Queueable;

    protected string $filename;
    protected int $count;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $filename, int $count)
    {
        $this->filename = $filename;
        $this->count = $count;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Filialen-Export abgeschlossen')
            ->line('Ihr Filialen-Export wurde erfolgreich abgeschlossen.')
            ->line("✓ {$this->count} Filiale(n) exportiert")
            ->action('Zum Dashboard', url('/customer/dashboard'))
            ->line('Sie können die Export-Datei über die Benachrichtigung herunterladen.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => "Filialen-Export abgeschlossen: {$this->count} Filiale(n) exportiert",
            'type' => 'branch_export',
            'filename' => $this->filename,
            'count' => $this->count,
        ];
    }
}
