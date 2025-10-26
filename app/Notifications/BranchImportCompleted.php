<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BranchImportCompleted extends Notification
{
    use Queueable;

    protected $imported;
    protected $failed;
    protected $skipped;
    protected $errors;

    /**
     * Create a new notification instance.
     */
    public function __construct($imported, $failed, $skipped = 0, $errors = [])
    {
        $this->imported = $imported;
        $this->failed = $failed;
        $this->skipped = $skipped;
        $this->errors = $errors;
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
        $message = (new MailMessage)
            ->subject('Filialen-Import abgeschlossen')
            ->greeting('Hallo ' . $notifiable->name . ',')
            ->line('Ihr Filialen-Import wurde abgeschlossen.');

        if ($this->imported > 0) {
            $message->line("✓ Erfolgreich importiert: {$this->imported} Filiale(n)");
        }

        if ($this->skipped > 0) {
            $message->line("⊘ Übersprungen (bereits vorhanden): {$this->skipped} Filiale(n)");
        }

        if ($this->failed > 0) {
            $message->line("✗ Fehlgeschlagen: {$this->failed} Filiale(n)");
        }

        $message->action('Zum Dashboard', url('/customer/dashboard'));

        if (!empty($this->errors) && count($this->errors) > 0) {
            $message->line('Fehlerdetails:');
            foreach (array_slice($this->errors, 0, 5) as $error) {
                $message->line('- ' . $error);
            }
        }

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $parts = [];

        if ($this->imported > 0) {
            $parts[] = "{$this->imported} importiert";
        }

        if ($this->skipped > 0) {
            $parts[] = "{$this->skipped} übersprungen";
        }

        if ($this->failed > 0) {
            $parts[] = "{$this->failed} fehlgeschlagen";
        }

        $message = "Import abgeschlossen: " . implode(', ', $parts);

        return [
            'type' => 'branch_import_completed',
            'imported' => $this->imported,
            'failed' => $this->failed,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
            'message' => $message,
        ];
    }
}
