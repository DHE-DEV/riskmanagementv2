<?php

namespace App\Console\Commands;

use App\Mail\AdminTaskDueMail;
use App\Mail\AdminTaskReminderMail;
use App\Models\AdminTask;
use App\Models\AdminTaskReminder;
use Illuminate\Console\Command;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Verschickt die zeitgesteuerten Mails der Aufgabenverwaltung:
 *
 * - Erinnerungen (beliebig viele je Aufgabe) an die jeweils gewaehlte Person,
 *   sonst an die Person bzw. das Team, bei dem die Aufgabe liegt.
 * - "Faellig und nicht erledigt" am Faelligkeitstag an Bearbeiter und
 *   Verantwortliche.
 *
 * Bei einem Team entscheidet dessen Einstellung, ob die zentrale Adresse
 * oder jedes Mitglied einzeln angeschrieben wird.
 *
 * Jede Mail geht genau einmal raus (sent_at der Erinnerung, due_notified_at
 * der Aufgabe). Wird der Zeitpunkt verschoben, wird die Marke zurueckgesetzt.
 */
class SendAdminTaskReminders extends Command
{
    /** Mails zur Faelligkeit gehen nicht mitten in der Nacht raus. */
    private const DUE_MAIL_FROM_HOUR = 7;

    protected $signature = 'tasks:send-reminders';

    protected $description = 'Versendet Erinnerungen und Fälligkeits-Mails zu Aufgaben im Admin-Bereich';

    public function handle(): int
    {
        $reminders = $this->sendReminders();
        $due = $this->sendDueNotices();

        $this->info("{$reminders} Erinnerung(en), {$due} Fälligkeits-Mail(s) versendet.");

        return self::SUCCESS;
    }

    protected function sendReminders(): int
    {
        $reminders = AdminTaskReminder::query()
            ->whereNull('sent_at')
            ->where('remind_at', '<=', now())
            // Nur fuer offene (und nicht geloeschte) Aufgaben.
            ->whereHas('task', fn ($query) => $query->open())
            ->with(['user', 'task.nextAssignee', 'task.nextAssigneeTeam.users', 'task.responsible', 'task.responsibleTeam.users', 'task.creator', 'task.category', 'task.subject'])
            ->orderBy('remind_at')
            ->get();

        $sent = 0;

        foreach ($reminders as $reminder) {
            $delivered = $this->deliver(
                $reminder->task,
                $reminder->recipientEmails(),
                new AdminTaskReminderMail($reminder->task, $reminder->note),
            );

            if ($delivered === null) {
                continue;
            }

            $sent += $delivered;

            $reminder->forceFill(['sent_at' => now()])->save();
        }

        return $sent;
    }

    protected function sendDueNotices(): int
    {
        if (now()->hour < self::DUE_MAIL_FROM_HOUR) {
            return 0;
        }

        $tasks = AdminTask::query()
            ->open()
            ->whereNotNull('due_date')
            ->where('due_date', '<=', today())
            ->whereNull('due_notified_at')
            ->with(['nextAssignee', 'nextAssigneeTeam.users', 'responsible', 'responsibleTeam.users', 'creator', 'category', 'subject'])
            ->get();

        $sent = 0;

        foreach ($tasks as $task) {
            $delivered = $this->deliver(
                $task,
                array_merge($task->handlerEmails(), $task->responsibleEmails()),
                new AdminTaskDueMail($task),
            );

            if ($delivered === null) {
                continue;
            }

            $sent += $delivered;

            $task->forceFill(['due_notified_at' => now()])->saveQuietly();
        }

        return $sent;
    }

    /**
     * @param  array<int, ?string>  $recipients
     * @return int|null Zahl der versendeten Mails; null bei einem Fehler –
     *                  dann wird die Aufgabe nicht markiert und der naechste Lauf versucht es erneut.
     */
    protected function deliver(AdminTask $task, array $recipients, Mailable $mail): ?int
    {
        $sent = 0;

        foreach (array_unique(array_filter($recipients)) as $email) {
            try {
                Mail::to($email)->send(clone $mail);
                $sent++;
            } catch (\Throwable $e) {
                Log::error('Mail zur Aufgabe konnte nicht versendet werden', [
                    'task_id' => $task->id,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        }

        return $sent;
    }
}
